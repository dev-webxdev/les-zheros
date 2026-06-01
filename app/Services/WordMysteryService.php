<?php

namespace App\Services;

use App\Models\User;
use App\Models\GuildSetting;
use App\Models\WordMysteryAttempt;
use App\Models\WordMysteryReward;
use App\Models\WordMysteryWord;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WordMysteryService
{
    public const MAX_ATTEMPTS = 6;

    public function validDifficulty(string $difficulty): string
    {
        return array_key_exists($difficulty, WordMysteryWord::DIFFICULTIES) ? $difficulty : 'normal';
    }

    public function wordOfTheDay(string $difficulty): ?WordMysteryWord
    {
        return WordMysteryWord::query()
            ->where('difficulty', $this->validDifficulty($difficulty))
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereDate('active_date', today()->toDateString())
                    ->orWhereNull('active_date');
            })
            ->orderByRaw('case when active_date is null then 1 else 0 end')
            ->latest('active_date')
            ->latest()
            ->first();
    }

    /**
     * @return array<string, WordMysteryWord>
     */
    public function wordsOfTheDay(): array
    {
        return collect(array_keys(WordMysteryWord::DIFFICULTIES))
            ->mapWithKeys(fn (string $difficulty): array => [
                $difficulty => $this->wordOfTheDay($difficulty),
            ])
            ->filter()
            ->all();
    }

    public function currentAttempt(User $user, WordMysteryWord $word): ?WordMysteryAttempt
    {
        $attempt = WordMysteryAttempt::query()
            ->where('user_id', $user->id)
            ->where('word_id', $word->id)
            ->where('difficulty', $word->difficulty)
            ->first();

        if ($attempt?->has_won && ! $attempt->reward()->exists()) {
            $attempt->update([
                'attempts_count' => 0,
                'guesses' => [],
                'has_won' => false,
                'reward_earned' => 0,
                'played_at' => null,
            ]);

            return $attempt->refresh();
        }

        return $attempt;
    }

    public function hasWonToday(User $user): bool
    {
        return WordMysteryAttempt::query()
            ->where('user_id', $user->id)
            ->where('has_won', true)
            ->whereDate('played_at', today())
            ->whereHas('reward')
            ->exists();
    }

    /**
     * @return array{attempt: WordMysteryAttempt, has_won: bool, attempts_count: int, reward: int}
     */
    public function submitGuess(User $user, WordMysteryWord $word, string $guess): array
    {
        $guess = trim($guess);
        $normalizedGuess = $this->normalizeWord($guess);
        $normalizedWord = $this->normalizeWord($word->word);

        if (mb_strlen($normalizedGuess) !== mb_strlen($normalizedWord)) {
            throw new InvalidArgumentException('length:'.mb_strlen($normalizedWord));
        }

        return DB::transaction(function () use ($user, $word, $guess, $normalizedGuess, $normalizedWord): array {
            $attempt = WordMysteryAttempt::query()
                ->where('user_id', $user->id)
                ->where('word_id', $word->id)
                ->where('difficulty', $word->difficulty)
                ->lockForUpdate()
                ->first();

            if ($this->hasWonToday($user) && ! $attempt?->has_won) {
                throw new InvalidArgumentException('already_won_today');
            }

            if ($attempt?->has_won && ! $attempt->reward()->exists()) {
                $attempt->update([
                    'attempts_count' => 0,
                    'guesses' => [],
                    'has_won' => false,
                    'reward_earned' => 0,
                    'played_at' => null,
                ]);
                $attempt->refresh();
            }

            if ($attempt?->has_won) {
                throw new InvalidArgumentException('already_won_word');
            }

            if ($attempt?->hasLost()) {
                throw new InvalidArgumentException('attempts_used');
            }

            $attempt ??= WordMysteryAttempt::create([
                'user_id' => $user->id,
                'word_id' => $word->id,
                'difficulty' => $word->difficulty,
                'guesses' => [],
                'played_at' => now(),
            ]);

            $attemptsCount = $attempt->attempts_count + 1;
            $hasWon = $normalizedGuess === $normalizedWord;
            $reward = $hasWon ? $this->calculateReward($word, $attemptsCount) : 0;
            $guesses = $attempt->guesses ?? [];
            $guesses[] = [
                'word' => $guess,
                'result' => $this->evaluateGuess($normalizedGuess, $normalizedWord),
            ];

            $attempt->update([
                'attempts_count' => $attemptsCount,
                'guesses' => $guesses,
                'has_won' => $hasWon,
                'reward_earned' => $reward,
                'played_at' => now(),
            ]);

            if ($hasWon && $reward > 0) {
                WordMysteryReward::firstOrCreate(
                    ['game_attempt_id' => $attempt->id],
                    [
                        'user_id' => $user->id,
                        'amount' => $reward,
                        'status' => 'pending',
                    ],
                );
            }

            return [
                'attempt' => $attempt->refresh(),
                'has_won' => $hasWon,
                'attempts_count' => $attemptsCount,
                'reward' => $reward,
            ];
        });
    }

    public function normalizeWord(string $word): string
    {
        return Str::of($word)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z]/', '')
            ->toString();
    }

    /**
     * @return list<string>
     */
    public function evaluateGuess(string $guess, string $word): array
    {
        $guessLetters = mb_str_split($guess);
        $wordLetters = mb_str_split($word);
        $result = array_fill(0, count($guessLetters), 'absent');
        $remaining = [];

        foreach ($wordLetters as $index => $letter) {
            if (($guessLetters[$index] ?? null) === $letter) {
                $result[$index] = 'correct';
            } else {
                $remaining[$letter] = ($remaining[$letter] ?? 0) + 1;
            }
        }

        foreach ($guessLetters as $index => $letter) {
            if ($result[$index] === 'correct') {
                continue;
            }

            if (($remaining[$letter] ?? 0) > 0) {
                $result[$index] = 'present';
                $remaining[$letter] -= 1;
            }
        }

        return $result;
    }

    public function calculateReward(WordMysteryWord $word, int $attemptsCount): int
    {
        return $this->rewardSteps($word)[$attemptsCount] ?? 0;
    }

    /**
     * @return list<array{label: string, amount: int}>
     */
    public function rewardRows(WordMysteryWord $word): array
    {
        return collect($this->rewardSteps($word))
            ->map(fn (int $amount, int $attempt): array => [
                'label' => $attempt.' essai'.($attempt > 1 ? 's' : ''),
                'amount' => $amount,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function rewardSteps(WordMysteryWord|string $wordOrDifficulty, ?int $baseReward = null): array
    {
        if ($wordOrDifficulty instanceof WordMysteryWord) {
            $steps = $wordOrDifficulty->reward_steps;

            if (is_array($steps) && $steps !== []) {
                return collect(range(1, self::MAX_ATTEMPTS))
                    ->mapWithKeys(fn (int $attempt): array => [$attempt => (int) ($steps[$attempt] ?? $steps[(string) $attempt] ?? 0)])
                    ->all();
            }

            $difficulty = $wordOrDifficulty->difficulty;
            $baseReward ??= $wordOrDifficulty->reward_base;

            if ($baseReward > 0) {
                return $this->fallbackRewardSteps($baseReward);
            }
        } else {
            $difficulty = $wordOrDifficulty;
        }

        $configured = GuildSetting::wordMysteryRewards()[$difficulty] ?? [];

        if ($configured !== []) {
            if (isset($configured['base'], $configured['bonuses']) && is_array($configured['bonuses'])) {
                $baseReward = (int) $configured['base'];

                return collect(range(1, self::MAX_ATTEMPTS))
                    ->mapWithKeys(fn (int $attempt): array => [
                        $attempt => (int) round($baseReward * (1 + ((int) ($configured['bonuses'][$attempt] ?? 0) / 100))),
                    ])
                    ->all();
            }

            return collect(range(1, self::MAX_ATTEMPTS))
                ->mapWithKeys(fn (int $attempt): array => [$attempt => (int) ($configured[$attempt] ?? $configured[(string) $attempt] ?? 0)])
                ->all();
        }

        return $this->fallbackRewardSteps($baseReward ?? 0);
    }

    /**
     * @return array<int, int>
     */
    private function fallbackRewardSteps(int $baseReward): array
    {
        return [
            1 => (int) round($baseReward * 1.2),
            2 => (int) round($baseReward * 1.1),
            3 => $baseReward,
            4 => (int) round($baseReward * 0.9),
            5 => (int) round($baseReward * 0.8),
            6 => (int) round($baseReward * 0.7),
        ];
    }

    public function parseReward(int|string $value): int
    {
        return (int) str_replace(' ', '', (string) $value);
    }

    public function generateWeek(CarbonInterface|string $weekStart, ?array $rewardSteps = null): int
    {
        $weekStart = $weekStart instanceof CarbonInterface
            ? CarbonImmutable::parse($weekStart)->startOfWeek(CarbonInterface::MONDAY)
            : CarbonImmutable::parse($weekStart)->startOfWeek(CarbonInterface::MONDAY);

        return $this->generateRange($weekStart, $weekStart->addDays(6), $rewardSteps);
    }

    public function generateRange(CarbonInterface|string $startDate, CarbonInterface|string $endDate, ?array $rewardSteps = null): int
    {
        $created = 0;
        $startDate = CarbonImmutable::parse($startDate)->startOfDay();
        $endDate = CarbonImmutable::parse($endDate)->startOfDay();
        $weekCursor = $startDate->startOfWeek(CarbonInterface::MONDAY);

        while ($weekCursor->lte($endDate)) {
            foreach ($this->generatedWeekRows($weekCursor, $rewardSteps) as $difficulty => $rows) {
                foreach ($rows as $row) {
                    $activeDate = CarbonImmutable::parse($row['active_date']);

                    if ($activeDate->lt($startDate) || $activeDate->gt($endDate)) {
                        continue;
                    }

                    $word = $this->wordForDate($difficulty, $row['active_date']);

                    if (! $word) {
                        $word = new WordMysteryWord([
                            'difficulty' => $difficulty,
                            'active_date' => $row['active_date'],
                            'word' => $row['word'],
                            'hint' => $row['hint'],
                            'reward_base' => $row['reward_base'],
                            'reward_steps' => $row['reward_steps'],
                            'is_active' => true,
                        ]);
                    } else {
                        $word->fill([
                            'word' => $row['word'],
                            'hint' => $row['hint'],
                            'reward_base' => $row['reward_base'],
                            'reward_steps' => $row['reward_steps'],
                            'is_active' => true,
                        ]);
                    }

                    if ($word->trashed()) {
                        $word->restore();
                    }

                    $word->save();
                    $created++;
                }
            }

            $weekCursor = $weekCursor->addWeek();
        }

        return $created;
    }

    /**
     * @return array{generated: int, restored: int, deleted: int}
     */
    public function syncCalendar(int $months = 6): array
    {
        $today = CarbonImmutable::parse(today());
        $endDate = $today->addMonthsNoOverflow($months)->endOfMonth();
        $deleted = WordMysteryWord::query()
            ->whereNotNull('active_date')
            ->whereDate('active_date', '<', $today->toDateString())
            ->delete();
        $generated = 0;
        $restored = 0;
        $weekCursor = $today->startOfWeek(CarbonInterface::MONDAY);

        while ($weekCursor->lte($endDate)) {
            foreach ($this->generatedWeekRows($weekCursor) as $difficulty => $rows) {
                foreach ($rows as $row) {
                    $activeDate = CarbonImmutable::parse($row['active_date']);

                    if ($activeDate->lt($today) || $activeDate->gt($endDate)) {
                        continue;
                    }

                    $word = $this->wordForDate($difficulty, $row['active_date']);

                    if (! $word) {
                        WordMysteryWord::create([
                            'difficulty' => $difficulty,
                            'active_date' => $row['active_date'],
                            'word' => $row['word'],
                            'hint' => $row['hint'],
                            'reward_base' => $row['reward_base'],
                            'reward_steps' => $row['reward_steps'],
                            'is_active' => true,
                        ]);
                        $generated++;
                    } elseif ($word->trashed()) {
                        $word->restore();
                        $restored++;
                    }
                }
            }

            $weekCursor = $weekCursor->addWeek();
        }

        return [
            'generated' => $generated,
            'restored' => $restored,
            'deleted' => $deleted,
        ];
    }

    /**
     * @return array<string, list<array{word: string, hint: string, reward_base: int, reward_steps: array<int, int>, active_date: string}>>
     */
    public function generatedWeekRows(CarbonInterface|string $weekStart, ?array $rewardSteps = null): array
    {
        $weekStart = $weekStart instanceof CarbonInterface
            ? CarbonImmutable::parse($weekStart)->startOfWeek(CarbonInterface::MONDAY)
            : CarbonImmutable::parse($weekStart)->startOfWeek(CarbonInterface::MONDAY);
        $rows = [];

        foreach (WordMysteryWord::DIFFICULTIES as $difficulty => $label) {
            $words = $this->availableBankForWeek($difficulty, $weekStart);
            $steps = $rewardSteps[$difficulty] ?? $this->rewardSteps($difficulty);

            foreach (range(0, 6) as $dayIndex) {
                $entry = $words === [] ? null : $words[$dayIndex % count($words)];

                if (! $entry) {
                    continue;
                }

                $rows[$difficulty][] = [
                    'word' => $entry['word'],
                    'hint' => $entry['hint'],
                    'reward_base' => $steps[3] ?? max($steps),
                    'reward_steps' => $steps,
                    'active_date' => $weekStart->addDays($dayIndex)->format('Y-m-d'),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<array{word: string, hint: string}>
     */
    private function availableBankForWeek(string $difficulty, CarbonImmutable $weekStart): array
    {
        $weekEnd = $weekStart->addDays(6);
        $usedWords = WordMysteryWord::withTrashed()
            ->where('difficulty', $difficulty)
            ->whereNotNull('active_date')
            ->where(function ($query) use ($weekStart, $weekEnd): void {
                $query->whereDate('active_date', '<', $weekStart->toDateString())
                    ->orWhereDate('active_date', '>', $weekEnd->toDateString());
            })
            ->pluck('word')
            ->map(fn (string $word): string => $this->normalizeWord($word))
            ->all();
        $usedWords = array_flip($usedWords);

        $availableWords = array_values(array_filter(
            config("word_mystery.bank.$difficulty", []),
            fn (array $entry): bool => $this->hasExpectedLength($difficulty, $entry['word'])
                && ! isset($usedWords[$this->normalizeWord($entry['word'])]),
        ));
        $availableWords = $this->uniqueBankEntries($availableWords);

        if (count($availableWords) < 7) {
            $availableWords = array_values(array_filter(
                config("word_mystery.bank.$difficulty", []),
                fn (array $entry): bool => $this->hasExpectedLength($difficulty, $entry['word']),
            ));
            $availableWords = $this->uniqueBankEntries($availableWords);
        }

        usort($availableWords, fn (array $first, array $second): int => strcmp(
            md5($weekStart->format('Y-m-d').$difficulty.$first['word']),
            md5($weekStart->format('Y-m-d').$difficulty.$second['word']),
        ));

        return $availableWords;
    }

    /**
     * @param list<array{word: string, hint: string}> $entries
     * @return list<array{word: string, hint: string}>
     */
    private function uniqueBankEntries(array $entries): array
    {
        $seen = [];

        return array_values(array_filter($entries, function (array $entry) use (&$seen): bool {
            $word = $this->normalizeWord($entry['word']);

            if (isset($seen[$word])) {
                return false;
            }

            $seen[$word] = true;

            return true;
        }));
    }

    private function hasExpectedLength(string $difficulty, string $word): bool
    {
        $length = config("word_mystery.lengths.$difficulty");

        return ! is_int($length) || mb_strlen($this->normalizeWord($word)) === $length;
    }

    private function wordForDate(string $difficulty, string $activeDate): ?WordMysteryWord
    {
        return WordMysteryWord::withTrashed()
            ->where('difficulty', $difficulty)
            ->whereDate('active_date', $activeDate)
            ->orderByRaw('case when deleted_at is null then 0 else 1 end')
            ->latest('updated_at')
            ->first();
    }
}
