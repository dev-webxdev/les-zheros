<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordMysteryReward;
use App\Models\WordMysteryWord;
use App\Services\WordMysteryService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WordMysteryController extends Controller
{
    public function __construct(private readonly WordMysteryService $wordMystery)
    {
    }

    public function index(Request $request): View
    {
        $today = today()->toDateString();
        $words = WordMysteryWord::query()
            ->withCount('attempts')
            ->whereDate('active_date', '>=', $today)
            ->orderBy('active_date')
            ->orderByRaw("case difficulty when 'easy' then 1 when 'normal' then 2 when 'hard' then 3 else 4 end")
            ->orderBy('word')
            ->get();

        $wordRows = $words
            ->groupBy(fn (WordMysteryWord $word): string => $word->active_date->format('Y-m').'|'.$word->difficulty)
            ->map(function ($rowWords, string $key): array {
                [$month, $difficulty] = explode('|', $key);
                $monthDate = CarbonImmutable::parse($month.'-01');

                return [
                    'month' => $month,
                    'month_label' => ucfirst($monthDate->translatedFormat('F Y')),
                    'difficulty' => $difficulty,
                    'difficulty_label' => WordMysteryWord::DIFFICULTIES[$difficulty] ?? ucfirst($difficulty),
                    'words' => $rowWords->values(),
                    'attempts_count' => $rowWords->sum('attempts_count'),
                    'reward_base' => $rowWords->first()?->reward_base ?? 0,
                    'all_active' => $rowWords->every(fn (WordMysteryWord $word): bool => $word->is_active),
                ];
            })
            ->values();
        $wordsPerPage = 3;
        $wordRows = new LengthAwarePaginator(
            $wordRows->forPage((int) $request->query('mots', 1), $wordsPerPage)->values(),
            $wordRows->count(),
            $wordsPerPage,
            (int) $request->query('mots', 1),
            [
                'path' => $request->url(),
                'pageName' => 'mots',
                'query' => $request->except('mots'),
            ],
        );

        return view('admin.admin-word-mystery', [
            'wordRows' => $wordRows,
            'wordsCount' => $words->count(),
            'rewards' => WordMysteryReward::query()
                ->with(['user', 'attempt.word'])
                ->latest()
                ->paginate(12, ['*'], 'recompenses'),
            'canForceDeleteWordMystery' => $this->canManageWordMysteryTrash($request),
        ]);
    }

    public function editGroup(string $month, string $difficulty): View
    {
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month) === 1, 404);
        abort_unless(array_key_exists($difficulty, WordMysteryWord::DIFFICULTIES), 404);

        [$monthStart, $monthEnd] = $this->monthRange($month);
        $visibleStart = $monthStart->lt(today()) ? CarbonImmutable::parse(today()) : $monthStart;
        $words = WordMysteryWord::query()
            ->where('difficulty', $difficulty)
            ->whereBetween('active_date', [$visibleStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('active_date')
            ->get();

        return view('admin.admin-word-mystery-group-form', [
            'month' => $month,
            'monthLabel' => ucfirst($monthStart->translatedFormat('F Y')),
            'difficulty' => $difficulty,
            'difficultyLabel' => WordMysteryWord::DIFFICULTIES[$difficulty],
            'words' => $words,
        ]);
    }

    public function updateGroup(Request $request, string $month, string $difficulty): RedirectResponse
    {
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month) === 1, 404);
        abort_unless(array_key_exists($difficulty, WordMysteryWord::DIFFICULTIES), 404);

        [$monthStart, $monthEnd] = $this->monthRange($month);
        $visibleStart = $monthStart->lt(today()) ? CarbonImmutable::parse(today()) : $monthStart;
        $validator = Validator::make($request->all(), [
            'words' => ['required', 'array'],
            'words.*.id' => ['required', 'integer'],
            'words.*.word' => ['required', 'string', 'max:40', 'regex:/^[\pL\'-]+$/u'],
            'words.*.hint' => ['required', 'string', 'max:255'],
        ], [
            'words.*.word.regex' => 'Les mots ne peuvent contenir que des lettres.',
        ]);
        $validator->after(function ($validator) use ($request, $difficulty): void {
            foreach ($request->input('words', []) as $index => $row) {
                if (! $this->wordHasExpectedLength($difficulty, (string) ($row['word'] ?? ''))) {
                    $validator->errors()->add("words.$index.word", $this->wordLengthMessage($difficulty));
                }
            }
        });
        $validated = $validator->validate();

        $updated = 0;

        foreach ($validated['words'] as $row) {
            $word = WordMysteryWord::query()
                ->whereKey($row['id'])
                ->where('difficulty', $difficulty)
                ->whereBetween('active_date', [$visibleStart->toDateString(), $monthEnd->toDateString()])
                ->first();

            if (! $word) {
                continue;
            }

            $word->update([
                'word' => trim($row['word']),
                'hint' => trim($row['hint']),
            ]);
            $updated++;
        }

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Mots modifies',
            'text' => $updated.' mot(s) '.$this->difficultyLabel($difficulty).' ont ete enregistres.',
            'type' => 'success',
        ]);
    }

    public function create(Request $request): View
    {
        $weekStart = CarbonImmutable::parse($request->query('semaine', today()))
            ->startOfWeek(CarbonInterface::MONDAY);
        $generationScope = $request->query('generer');
        $generationScope = in_array($generationScope, ['week', 'month', 'six_months'], true)
            ? $generationScope
            : null;
        [$periodStart, $periodEnd] = match ($generationScope) {
            'month' => [$weekStart->startOfMonth(), $weekStart->endOfMonth()],
            'six_months' => [$weekStart, $weekStart->addMonthsNoOverflow(6)->endOfMonth()],
            default => [$weekStart, $weekStart->addDays(6)],
        };
        $today = CarbonImmutable::parse(today());
        $periodStart = $periodStart->lt($today) ? $today : $periodStart;
        $periodEnd = $periodEnd->lt($periodStart) ? $periodStart : $periodEnd;
        $previewRows = $generationScope
            ? collect($this->generatedRowsForPeriod($periodStart, $periodEnd))
            : collect();
        $weekWords = $previewRows
            ->map(fn (array $row): WordMysteryWord => new WordMysteryWord($row))
            ->keyBy(fn (WordMysteryWord $word): string => $word->difficulty.'|'.$word->active_date?->format('Y-m-d'));
        $daysCount = $periodStart->diffInDays($periodEnd) + 1;

        return view('admin.admin-word-mystery-form', [
            'word' => new WordMysteryWord([
                'difficulty' => 'normal',
                'reward_base' => 25000,
                'active_date' => today(),
                'is_active' => true,
            ]),
            'weekStart' => $weekStart,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'weekDays' => collect(range(0, $daysCount - 1))
                ->map(fn (int $day): CarbonImmutable => $periodStart->addDays($day))
                ->all(),
            'weekWords' => $weekWords,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('weekly_words')) {
            return $this->storeWeek($request);
        }

        WordMysteryWord::create($this->payload($request));

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Mot ajoute',
            'text' => 'Le mot mystere est pret pour les joueurs.',
            'type' => 'success',
        ]);
    }

    public function generateWeek(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'week_start' => ['required', 'date'],
            'generation_scope' => ['required', Rule::in(['week', 'month', 'six_months'])],
        ]);
        $weekStart = CarbonImmutable::parse($validated['week_start'])
            ->startOfWeek(CarbonInterface::MONDAY);
        $scope = $validated['generation_scope'];

        if ($scope === 'month') {
            $periodStart = $weekStart->startOfMonth();
            $periodEnd = $weekStart->endOfMonth();
            $title = 'Mois prepare';
        } elseif ($scope === 'six_months') {
            $periodStart = $weekStart;
            $periodEnd = $weekStart->addMonthsNoOverflow(6)->endOfMonth();
            $title = 'Six mois prepares';
        } else {
            $periodStart = $weekStart;
            $periodEnd = $weekStart->addDays(6);
            $title = 'Semaine preparee';
        }
        $today = CarbonImmutable::parse(today());
        $periodStart = $periodStart->lt($today) ? $today : $periodStart;
        $periodEnd = $periodEnd->lt($periodStart) ? $periodStart : $periodEnd;
        $rows = $this->generatedRowsForPeriod($periodStart, $periodEnd);
        $count = count($rows);

        return redirect()
            ->route('admin.mot-mystere.create', [
                'semaine' => $weekStart->toDateString(),
                'generer' => $scope,
            ])
            ->with('admin_toast', [
                'title' => $title,
                'text' => $count.' mot(s) ont rempli le formulaire. Clique sur Enregistrer pour les sauvegarder.',
                'type' => 'success',
            ]);
    }

    public function edit(WordMysteryWord $word): View
    {
        return view('admin.admin-word-mystery-form', [
            'word' => $word,
        ]);
    }

    public function update(Request $request, WordMysteryWord $word): RedirectResponse
    {
        $word->update($this->payload($request));

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Mot modifie',
            'text' => 'Les reglages du mot mystere ont ete enregistres.',
            'type' => 'success',
        ]);
    }

    public function destroy(WordMysteryWord $word): RedirectResponse
    {
        $word->delete();

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Mot en corbeille',
            'text' => 'Le mot a ete deplace dans la corbeille.',
            'type' => 'warning',
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['trash'])],
            'scope' => ['nullable', Rule::in(['all'])],
            'ids' => [$request->input('scope') === 'all' ? 'nullable' : 'required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $query = ($validated['scope'] ?? null) === 'all'
            ? WordMysteryWord::query()->whereDate('active_date', '>=', today()->toDateString())
            : WordMysteryWord::whereKey($validated['ids'] ?? []);
        $words = $query->get();

        $words->each->delete();

        return back()->with('admin_toast', [
            'title' => 'Action groupee terminee',
            'text' => $words->count().' mot(s) deplace(s) dans la corbeille.',
            'type' => 'warning',
        ]);
    }

    public function destroyReward(WordMysteryReward $reward): RedirectResponse
    {
        $reward->attempt?->update([
            'attempts_count' => 0,
            'guesses' => [],
            'has_won' => false,
            'reward_earned' => 0,
            'played_at' => null,
        ]);
        $reward->delete();

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Recompense en corbeille',
            'text' => 'La recompense a ete deplacee dans la corbeille.',
            'type' => 'warning',
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-word-mystery-trash', [
            'words' => WordMysteryWord::onlyTrashed()
                ->withCount('attempts')
                ->latest('deleted_at')
                ->paginate(12),
            'rewards' => WordMysteryReward::onlyTrashed()
                ->with(['user', 'attempt.word'])
                ->latest('deleted_at')
                ->paginate(12, ['*'], 'recompenses'),
            'canForceDeleteWordMystery' => $this->canManageWordMysteryTrash(request()),
        ]);
    }

    public function restoreWord(int $word): RedirectResponse
    {
        WordMysteryWord::onlyTrashed()->findOrFail($word)->restore();

        return redirect()->route('admin.mot-mystere.trash')->with('admin_toast', [
            'title' => 'Mot restaure',
            'text' => 'Le mot est de retour dans la liste.',
            'type' => 'success',
        ]);
    }

    public function forceDeleteWord(int $word): RedirectResponse
    {
        abort_unless($this->canManageWordMysteryTrash(request()), 403);

        WordMysteryWord::onlyTrashed()->findOrFail($word)->forceDelete();

        return redirect()->route('admin.mot-mystere.trash')->with('admin_toast', [
            'title' => 'Mot supprime',
            'text' => 'Le mot a ete supprime definitivement.',
            'type' => 'warning',
        ]);
    }

    public function restoreReward(int $reward): RedirectResponse
    {
        $reward = WordMysteryReward::onlyTrashed()->findOrFail($reward);
        $reward->restore();
        $reward->attempt?->update([
            'has_won' => true,
            'reward_earned' => $reward->amount,
            'played_at' => $reward->attempt?->played_at ?? now(),
        ]);

        return redirect()->route('admin.mot-mystere.trash')->with('admin_toast', [
            'title' => 'Recompense restauree',
            'text' => 'La recompense est de retour dans la liste.',
            'type' => 'success',
        ]);
    }

    public function forceDeleteReward(int $reward): RedirectResponse
    {
        abort_unless($this->canManageWordMysteryTrash(request()), 403);

        WordMysteryReward::onlyTrashed()->findOrFail($reward)->forceDelete();

        return redirect()->route('admin.mot-mystere.trash')->with('admin_toast', [
            'title' => 'Recompense supprimee',
            'text' => 'La recompense a ete supprimee definitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        abort_unless($this->canManageWordMysteryTrash(request()), 403);

        WordMysteryReward::onlyTrashed()->forceDelete();
        WordMysteryWord::onlyTrashed()->forceDelete();

        return redirect()->route('admin.mot-mystere.trash')->with('admin_toast', [
            'title' => 'Corbeille videe',
            'text' => 'Tous les elements Mot Mystere en corbeille ont ete supprimes definitivement.',
            'type' => 'warning',
        ]);
    }

    public function updateReward(Request $request, WordMysteryReward $reward): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(WordMysteryReward::STATUSES))],
        ]);

        $reward->update($validated);

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Recompense mise a jour',
            'text' => 'Le statut du gain a bien ete change.',
            'type' => 'success',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'word' => ['required', 'string', 'max:40', 'regex:/^[\pL\'-]+$/u'],
            'hint' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', Rule::in(array_keys(WordMysteryWord::DIFFICULTIES))],
            'active_date' => ['nullable', 'date'],
            'is_active' => ['nullable'],
        ], [
            'word.regex' => 'Le mot ne peut contenir que des lettres.',
        ]);
        if (! $this->wordHasExpectedLength($validated['difficulty'], $validated['word'])) {
            throw ValidationException::withMessages([
                'word' => $this->wordLengthMessage($validated['difficulty']),
            ]);
        }
        $steps = $this->wordMystery->rewardSteps($validated['difficulty']);

        return [
            'word' => trim($validated['word']),
            'hint' => trim($validated['hint']),
            'difficulty' => $validated['difficulty'],
            'reward_base' => $steps[3] ?? max($steps),
            'reward_steps' => $steps,
            'active_date' => $validated['active_date'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function storeWeek(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'weekly_words' => ['required', 'array'],
            'weekly_words.*' => ['required', 'array'],
            'weekly_words.*.*.word' => ['required', 'string', 'max:40', 'regex:/^[\pL\'-]+$/u'],
            'weekly_words.*.*.hint' => ['required', 'string', 'max:255'],
            'weekly_words.*.*.active_date' => ['required', 'date'],
        ], [
            'weekly_words.*.*.word.regex' => 'Les mots ne peuvent contenir que des lettres.',
        ]);
        $validator->after(function ($validator) use ($request): void {
            foreach ($request->input('weekly_words', []) as $difficulty => $rows) {
                foreach ($rows as $index => $row) {
                    if (! $this->wordHasExpectedLength((string) $difficulty, (string) ($row['word'] ?? ''))) {
                        $validator->errors()->add("weekly_words.$difficulty.$index.word", $this->wordLengthMessage((string) $difficulty));
                    }
                }
            }
        });
        $validator->validate();

        $created = 0;

        foreach ($request->input('weekly_words', []) as $difficulty => $rows) {
            if (! array_key_exists($difficulty, WordMysteryWord::DIFFICULTIES)) {
                continue;
            }

            $steps = $this->wordMystery->rewardSteps($difficulty);

            foreach ($rows as $row) {
                WordMysteryWord::updateOrCreate(
                    [
                        'difficulty' => $difficulty,
                        'active_date' => $row['active_date'],
                    ],
                    [
                        'word' => trim((string) $row['word']),
                        'hint' => trim((string) $row['hint']),
                        'reward_base' => $steps[3] ?? max($steps),
                        'reward_steps' => $steps,
                        'is_active' => true,
                    ],
                );
                $created++;
            }
        }

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Semaine enregistree',
            'text' => $created.' mot(s) mystere ont ete prepares.',
            'type' => 'success',
        ]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function monthRange(string $month): array
    {
        $monthStart = CarbonImmutable::parse($month.'-01')->startOfMonth();

        return [$monthStart, $monthStart->endOfMonth()];
    }

    private function difficultyLabel(string $difficulty): string
    {
        return WordMysteryWord::DIFFICULTIES[$difficulty] ?? $difficulty;
    }

    private function wordHasExpectedLength(string $difficulty, string $word): bool
    {
        $length = config("word_mystery.lengths.$difficulty");

        return ! is_int($length) || mb_strlen($this->wordMystery->normalizeWord($word)) === $length;
    }

    private function wordLengthMessage(string $difficulty): string
    {
        $length = config("word_mystery.lengths.$difficulty");
        $label = $this->difficultyLabel($difficulty);

        return is_int($length)
            ? "Un mot $label doit contenir $length lettres."
            : 'La longueur du mot est invalide.';
    }

    private function canManageWordMysteryTrash(Request $request): bool
    {
        $user = $request->user();

        return (bool) (
            $user?->canForceDeleteInAdminArea('word_mystery')
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function generatedRowsForPeriod(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $rows = [];
        $weekCursor = $periodStart->startOfWeek(CarbonInterface::MONDAY);

        while ($weekCursor->lte($periodEnd)) {
            foreach ($this->wordMystery->generatedWeekRows($weekCursor) as $difficulty => $weekRows) {
                foreach ($weekRows as $row) {
                    $activeDate = CarbonImmutable::parse($row['active_date']);

                    if ($activeDate->lt($periodStart) || $activeDate->gt($periodEnd)) {
                        continue;
                    }

                    $rows[] = [
                        'word' => $row['word'],
                        'hint' => $row['hint'],
                        'difficulty' => $difficulty,
                        'reward_base' => $row['reward_base'],
                        'reward_steps' => $row['reward_steps'],
                        'active_date' => $row['active_date'],
                        'is_active' => true,
                    ];
                }
            }

            $weekCursor = $weekCursor->addWeek();
        }

        return $rows;
    }

}
