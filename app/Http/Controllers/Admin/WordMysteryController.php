<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordMysteryReward;
use App\Models\WordMysteryWord;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WordMysteryController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-word-mystery', [
            'words' => WordMysteryWord::query()
                ->withCount('attempts')
                ->latest('active_date')
                ->latest()
                ->paginate(12),
            'rewards' => WordMysteryReward::query()
                ->with(['user', 'attempt.word'])
                ->latest()
                ->paginate(12, ['*'], 'recompenses'),
        ]);
    }

    public function create(Request $request): View
    {
        $weekStart = CarbonImmutable::parse($request->query('semaine', today()))
            ->startOfWeek(CarbonInterface::MONDAY);

        return view('admin.admin-word-mystery-form', [
            'word' => new WordMysteryWord([
                'difficulty' => 'normal',
                'reward_base' => 25000,
                'active_date' => today(),
                'is_active' => true,
            ]),
            'weekStart' => $weekStart,
            'weekDays' => collect(range(0, 6))
                ->map(fn (int $day): CarbonImmutable => $weekStart->addDays($day))
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('weekly_words')) {
            return $this->storeWeek($request);
        }

        WordMysteryWord::create($this->payload($request));

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Mot ajouté',
            'text' => 'Le mot mystère est prêt pour les joueurs.',
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
            'title' => 'Mot modifié',
            'text' => 'Les réglages du mot mystère ont été enregistrés.',
            'type' => 'success',
        ]);
    }

    public function destroy(WordMysteryWord $word): RedirectResponse
    {
        $word->delete();

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Mot supprimé',
            'text' => 'Le mot a été retiré de la rotation.',
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
            'title' => 'Récompense mise à jour',
            'text' => 'Le statut du gain a bien été changé.',
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
            'reward_base' => ['required', 'string', 'max:16', 'regex:/^[0-9 ]+$/'],
            'active_date' => ['nullable', 'date'],
            'is_active' => ['nullable'],
        ], [
            'word.regex' => 'Le mot ne peut contenir que des lettres.',
            'reward_base.regex' => 'Le gain doit contenir uniquement des chiffres et espaces.',
        ]);

        return [
            'word' => trim($validated['word']),
            'hint' => trim($validated['hint']),
            'difficulty' => $validated['difficulty'],
            'reward_base' => $this->parseReward($validated['reward_base']),
            'active_date' => $validated['active_date'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function storeWeek(Request $request): RedirectResponse
    {
        Validator::make($request->all(), [
            'weekly_words' => ['required', 'array'],
            'weekly_words.*' => ['required', 'array'],
            'weekly_words.*.*.word' => ['required', 'string', 'max:40', 'regex:/^[\pL\'-]+$/u'],
            'weekly_words.*.*.hint' => ['required', 'string', 'max:255'],
            'weekly_words.*.*.reward_base' => ['required', 'string', 'max:16', 'regex:/^[0-9 ]+$/'],
            'weekly_words.*.*.active_date' => ['required', 'date'],
        ], [
            'weekly_words.*.*.word.regex' => 'Les mots ne peuvent contenir que des lettres.',
            'weekly_words.*.*.reward_base.regex' => 'Le gain doit contenir uniquement des chiffres et espaces.',
        ])->validate();

        $created = 0;

        foreach ($request->input('weekly_words', []) as $difficulty => $rows) {
            if (! array_key_exists($difficulty, WordMysteryWord::DIFFICULTIES)) {
                continue;
            }

            foreach ($rows as $row) {
                WordMysteryWord::updateOrCreate(
                    [
                        'difficulty' => $difficulty,
                        'active_date' => $row['active_date'],
                    ],
                    [
                        'word' => trim((string) $row['word']),
                        'hint' => trim((string) $row['hint']),
                        'reward_base' => $this->parseReward($row['reward_base']),
                        'is_active' => ! empty($row['is_active']),
                    ],
                );
                $created++;
            }
        }

        return redirect()->route('admin.mot-mystere.index')->with('admin_toast', [
            'title' => 'Semaine enregistrée',
            'text' => $created.' mot(s) mystère ont été préparés.',
            'type' => 'success',
        ]);
    }

    private function parseReward(int|string $value): int
    {
        return (int) str_replace(' ', '', (string) $value);
    }
}
