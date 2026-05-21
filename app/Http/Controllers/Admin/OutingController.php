<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outing;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OutingController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-sorties', [
            'outings' => Outing::query()
                ->with('votes.user')
                ->withCount('votes')
                ->latest()
                ->paginate(12),
            'confirmedOuting' => Outing::query()
                ->with('votes.user')
                ->whereNotNull('confirmed_slot_id')
                ->where('is_published', true)
                ->get()
                ->filter(fn (Outing $outing): bool => $outing->confirmedSlotDateTime() !== null)
                ->filter(fn (Outing $outing): bool => Carbon::parse($outing->confirmedSlotDateTime())->greaterThanOrEqualTo(now()))
                ->sortBy(fn (Outing $outing): string => $outing->confirmedSlotDateTime())
                ->first(),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-sortie-create', [
            'outing' => new Outing([
                'places' => 8,
                'close_at' => now()->addDays(2)->setTime(20, 0),
                'schedule' => [],
                'is_published' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Outing::create($this->payload($request, true));

        return redirect()->route('admin.sorties.index')->with('admin_toast', [
            'title' => 'Sortie créée',
            'text' => 'Le vote est visible côté guilde.',
            'type' => 'success',
        ]);
    }

    public function edit(Outing $outing): View
    {
        return view('admin.admin-sortie-create', [
            'outing' => $outing,
        ]);
    }

    public function update(Request $request, Outing $outing): RedirectResponse
    {
        $payload = $this->payload($request);
        $outing->update($payload);

        if ($outing->confirmed_slot_id && ! $outing->hasSlot($outing->confirmed_slot_id)) {
            $outing->update([
                'confirmed_slot_id' => null,
                'confirmed_at' => null,
            ]);
        }

        return redirect()->route('admin.sorties.index')->with('admin_toast', [
            'title' => 'Sortie modifiée',
            'text' => 'Le vote a bien été mis à jour.',
            'type' => 'success',
        ]);
    }

    public function destroy(Outing $outing): RedirectResponse
    {
        $outing->delete();

        return redirect()->route('admin.sorties.index')->with('admin_toast', [
            'title' => 'Sortie en corbeille',
            'text' => 'La sortie a été déplacée dans la corbeille.',
            'type' => 'success',
        ]);
    }

    public function confirm(Request $request, Outing $outing): RedirectResponse
    {
        $outing->load('votes.user');
        $validated = $request->validate([
            'slot_id' => ['required', 'string', 'max:255'],
        ]);
        $slotId = $validated['slot_id'];

        if (! $outing->hasSlot($slotId)) {
            return redirect()->route('admin.sorties.index')->with('admin_toast', [
                'title' => 'Créneau invalide',
                'text' => 'Choisis un créneau existant avant de valider la sortie.',
                'type' => 'warning',
            ]);
        }

        if ($outing->votes->where('slot_id', $slotId)->isEmpty()) {
            return redirect()->route('admin.sorties.index')->with('admin_toast', [
                'title' => 'Aucun inscrit',
                'text' => 'Ce creneau ne peut pas etre valide tant que personne ne l a choisi.',
                'type' => 'warning',
            ]);
        }

        $outing->update([
            'confirmed_slot_id' => $slotId,
            'confirmed_at' => now(),
        ]);

        return redirect()->route('admin.sorties.index')->with('admin_toast', [
            'title' => 'Sortie validée',
            'text' => 'Le créneau retenu et les joueurs à inviter sont prêts.',
            'type' => 'success',
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-sorties-trash', [
            'outings' => Outing::onlyTrashed()
                ->withCount('votes')
                ->latest('deleted_at')
                ->paginate(12),
        ]);
    }

    public function restore(int $outing): RedirectResponse
    {
        Outing::onlyTrashed()->findOrFail($outing)->restore();

        return redirect()->route('admin.sorties.trash')->with('admin_toast', [
            'title' => 'Sortie restaurée',
            'text' => 'Elle est de retour dans la liste.',
            'type' => 'success',
        ]);
    }

    public function forceDelete(int $outing): RedirectResponse
    {
        Outing::onlyTrashed()->findOrFail($outing)->forceDelete();

        return redirect()->route('admin.sorties.trash')->with('admin_toast', [
            'title' => 'Sortie supprimée',
            'text' => 'Elle a été supprimée définitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        Outing::onlyTrashed()->forceDelete();

        return redirect()->route('admin.sorties.trash')->with('admin_toast', [
            'title' => 'Corbeille vidée',
            'text' => 'Toutes les sorties en corbeille ont été supprimées définitivement.',
            'type' => 'warning',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, bool $publishByDefault = false): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'places' => ['required', 'integer', 'between:1,16'],
            'close_at' => ['nullable', 'string', 'max:32'],
            'schedule' => ['required', 'json'],
            'published' => ['nullable'],
        ]);
        $closeAt = $this->parseCloseAt($validated['close_at'] ?? null);

        $schedule = collect(json_decode($validated['schedule'], true) ?: [])
            ->map(fn (array $day): array => [
                'date' => (string) ($day['date'] ?? ''),
                'times' => collect($day['times'] ?? [])
                    ->filter(fn ($time): bool => is_string($time) && preg_match('/^\d{2}:\d{2}$/', $time) === 1)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $day): bool => $day['date'] !== '' && count($day['times']) > 0)
            ->sortBy('date')
            ->values()
            ->all();

        validator(['schedule' => $schedule], [
            'schedule' => ['required', 'array', 'min:1'],
            'schedule.*.date' => ['required', 'date'],
            'schedule.*.times' => ['required', 'array', 'min:1'],
            'schedule.*.times.*' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ])->validate();

        $firstSlot = collect($schedule)
            ->flatMap(fn (array $day): array => collect($day['times'] ?? [])
                ->map(fn (string $time): string => $day['date'].' '.$time)
                ->all())
            ->sort()
            ->first();
        $latestCloseAt = $firstSlot ? Carbon::parse($firstSlot)->subHours(2)->format('Y-m-d H:i:s') : null;

        validator([
            'close_at' => $closeAt,
            'latest_close_at' => $latestCloseAt,
        ], [
            'close_at' => ['nullable', 'date', 'before_or_equal:latest_close_at'],
        ], [
            'close_at.before_or_equal' => 'La clôture des votes doit être au plus tard 2 heures avant le premier créneau de la sortie.',
        ])->validate();

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'places' => $validated['places'],
            'close_at' => $closeAt,
            'schedule' => $schedule,
            'is_published' => $publishByDefault ? true : $request->boolean('published'),
        ];
    }

    private function parseCloseAt(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y H:i', 'Y-m-d\TH:i', 'Y-m-d H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'close_at' => 'La clôture des votes doit respecter le format jj/mm/aaaa hh:mm.',
            ]);
        }
    }
}
