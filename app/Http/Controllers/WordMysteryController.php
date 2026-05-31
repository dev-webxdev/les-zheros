<?php

namespace App\Http\Controllers;

use App\Models\WordMysteryAttempt;
use App\Models\WordMysteryReward;
use App\Models\WordMysteryWord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WordMysteryController extends Controller
{
    public function show(Request $request): View
    {
        $difficulty = $this->validDifficulty((string) $request->query('difficulte', 'normal'));
        $word = $this->wordOfTheDay($difficulty);
        $attempt = $word && $request->user()
            ? $this->currentAttempt($request, $word)
            : null;

        return view('pages.mot-mystere', [
            'difficulty' => $difficulty,
            'word' => $word,
            'attempt' => $attempt,
            'hasWonToday' => $request->user() ? $this->hasWonToday($request) : false,
            'rewardPreview' => $word ? $this->rewardRows($word->reward_base) : [],
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'difficulty' => ['required', Rule::in(array_keys(WordMysteryWord::DIFFICULTIES))],
            'guess' => ['required', 'string', 'max:40', 'regex:/^[\pL\'-]+$/u'],
        ], [
            'guess.regex' => 'Le mot propose ne peut contenir que des lettres.',
        ]);

        $word = $this->wordOfTheDay($validated['difficulty']);

        if (! $word) {
            return back()->with('toast', [
                'title' => 'Mot indisponible',
                'text' => 'Aucun mot actif n est configure pour cette difficulte aujourd hui.',
                'type' => 'warning',
            ]);
        }

        $guess = trim($validated['guess']);
        $normalizedGuess = $this->normalizeWord($guess);
        $normalizedWord = $this->normalizeWord($word->word);

        if (mb_strlen($normalizedGuess) !== mb_strlen($normalizedWord)) {
            return back()->withInput()->with('toast', [
                'title' => 'Longueur incorrecte',
                'text' => 'Le mot mystere contient '.mb_strlen($normalizedWord).' lettres.',
                'type' => 'warning',
            ]);
        }

        $attempt = $this->currentAttempt($request, $word);

        if ($this->hasWonToday($request) && ! $attempt?->has_won) {
            return back()->with('toast', [
                'title' => 'Gain deja obtenu',
                'text' => 'Tu as deja gagne une recompense aujourd hui. Reviens demain pour un nouveau gain.',
                'type' => 'warning',
            ]);
        }

        if ($attempt?->has_won || $attempt?->hasLost()) {
            return back()->with('toast', [
                'title' => 'Partie terminee',
                'text' => $attempt->has_won ? 'Tu as deja trouve le mot du jour.' : 'Tes 6 essais sont deja utilises.',
                'type' => 'warning',
            ]);
        }

        $attempt ??= WordMysteryAttempt::create([
            'user_id' => $request->user()->id,
            'word_id' => $word->id,
            'difficulty' => $word->difficulty,
            'guesses' => [],
            'played_at' => now(),
        ]);

        $guesses = $attempt->guesses ?? [];
        $hasWon = $normalizedGuess === $normalizedWord;
        $attemptsCount = $attempt->attempts_count + 1;
        $reward = $hasWon ? $this->calculateReward($word->reward_base, $attemptsCount) : 0;

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
                    'user_id' => $request->user()->id,
                    'amount' => $reward,
                    'status' => 'pending',
                ],
            );
        }

        return redirect()
            ->route('mot-mystere', ['difficulte' => $word->difficulty])
            ->with('toast', [
                'title' => $hasWon ? 'Mot trouve' : ($attemptsCount >= 6 ? 'Partie terminee' : 'Essai enregistre'),
                'text' => $hasWon
                    ? 'Bravo, recompense en attente: '.number_format($reward, 0, ',', ' ').' kamas.'
                    : ($attemptsCount >= 6 ? 'Tu pourras retenter ta chance demain.' : 'Continue, il te reste '.(6 - $attemptsCount).' essai(s).'),
                'type' => $hasWon ? 'success' : ($attemptsCount >= 6 ? 'warning' : 'success'),
            ]);
    }

    private function wordOfTheDay(string $difficulty): ?WordMysteryWord
    {
        return WordMysteryWord::query()
            ->where('difficulty', $difficulty)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereDate('active_date', today())
                    ->orWhereNull('active_date');
            })
            ->orderByRaw('case when active_date is null then 1 else 0 end')
            ->latest('active_date')
            ->latest()
            ->first();
    }

    private function currentAttempt(Request $request, WordMysteryWord $word): ?WordMysteryAttempt
    {
        return WordMysteryAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('word_id', $word->id)
            ->where('difficulty', $word->difficulty)
            ->first();
    }

    private function hasWonToday(Request $request): bool
    {
        return WordMysteryAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('has_won', true)
            ->whereDate('played_at', today())
            ->exists();
    }

    private function validDifficulty(string $difficulty): string
    {
        return array_key_exists($difficulty, WordMysteryWord::DIFFICULTIES) ? $difficulty : 'normal';
    }

    private function normalizeWord(string $word): string
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
    private function evaluateGuess(string $guess, string $word): array
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

    private function calculateReward(int $baseReward, int $attemptsCount): int
    {
        if ($attemptsCount <= 2) {
            return (int) round($baseReward * 1.2);
        }

        if ($attemptsCount <= 4) {
            return $baseReward;
        }

        return (int) round($baseReward * 0.8);
    }

    /**
     * @return list<array{label: string, amount: int}>
     */
    private function rewardRows(int $baseReward): array
    {
        return [
            ['label' => '1-2 essais', 'amount' => (int) round($baseReward * 1.2)],
            ['label' => '3-4 essais', 'amount' => $baseReward],
            ['label' => '5-6 essais', 'amount' => (int) round($baseReward * 0.8)],
        ];
    }
}
