<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use App\Support\AdminActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ValidationController extends Controller
{
    public function index(Request $request): View
    {
        $allValidations = MissionValidation::query()
            ->with(['mission', 'user'])
            ->latest()
            ->get();
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'player' => (string) $request->query('player', 'all'),
            'status' => (string) $request->query('status', 'all'),
        ];
        $validationsQuery = MissionValidation::query()
            ->with(['mission', 'user'])
            ->when($filters['player'] !== 'all', function ($query) use ($filters): void {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('name', $filters['player']));
            })
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($innerQuery) use ($filters): void {
                    $innerQuery
                        ->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$filters['search'].'%'))
                        ->orWhereHas('mission', fn ($missionQuery) => $missionQuery->where('title', 'like', '%'.$filters['search'].'%'));
                });
            })
            ->latest();

        return view('admin.admin-validations', [
            'validations' => $validationsQuery->paginate(12)->withQueryString(),
            'players' => User::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'stats' => [
                'pending' => $allValidations->where('status', MissionValidation::PENDING)->count(),
                'validated' => $allValidations->where('status', MissionValidation::VALIDATED)->count(),
                'refused' => $allValidations->where('status', MissionValidation::REFUSED)->count(),
                'points' => $allValidations->sum(fn (MissionValidation $validation): float => $validation->points()),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-validation-create', [
            'validation' => new MissionValidation(['characters' => 1]),
            'missions' => Mission::query()->latest()->get(),
            'players' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = [
            ...$this->payload($request),
            'status' => MissionValidation::VALIDATED,
        ];
        $teammates = $this->teammatePayload($request);
        $players = User::query()
            ->whereIn('id', $teammates->pluck('user_id')->push($payload['user_id'])->unique())
            ->pluck('name', 'id');
        $groupMembers = collect([
            [
                'user_id' => $payload['user_id'],
                'name' => (string) ($players[$payload['user_id']] ?? 'Joueur'),
                'characters' => $payload['characters'],
            ],
        ])->merge($teammates->map(fn (array $teammate): array => [
            'user_id' => $teammate['user_id'],
            'name' => (string) ($players[$teammate['user_id']] ?? 'Coéquipier'),
            'characters' => $teammate['characters'],
        ]))->values();

        $groupMembers->each(function (array $member) use ($groupMembers, $payload): void {
            MissionValidation::create([
                ...$payload,
                'user_id' => $member['user_id'],
                'characters' => $member['characters'],
                'teammates' => $groupMembers
                    ->reject(fn (array $teammate): bool => $teammate['user_id'] === $member['user_id'])
                    ->values()
                    ->all(),
            ]);
        });

        AdminActivity::log('validations', 'created', 'Validation ajoutee', 'Declaration ajoutee depuis l administration.');

        return redirect()->route('admin.validations.index')->with('admin_toast', [
            'title' => 'Déclaration ajoutée',
            'text' => 'La validation est maintenant dans la liste.',
            'type' => 'success',
        ]);
    }

    public function edit(MissionValidation $validation): View
    {
        $groupValidations = $this->linkedValidations($validation);

        return view('admin.admin-validation-edit', [
            'validation' => $validation,
            'groupValidations' => $groupValidations,
            'missions' => Mission::query()->latest()->get(),
            'players' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, MissionValidation $validation): RedirectResponse
    {
        $groupValidations = $this->linkedValidations($validation);
        $payload = $this->payload($request, $validation);
        $validation->update($payload);

        AdminActivity::log('validations', 'updated', 'Validation modifiee', 'Declaration mise a jour.', $validation);

        if ($request->boolean('sync_group')) {
            $groupPayload = $request->validate([
                'group_validations' => ['nullable', 'array'],
                'group_validations.*.characters' => ['required', 'integer', 'between:1,8'],
                'group_validations.*.status' => ['required', Rule::in(array_keys(MissionValidation::STATUSES))],
            ]);

            $groupValidations
                ->each(function (MissionValidation $groupValidation) use ($groupPayload, $payload): void {
                    $row = $groupPayload['group_validations'][$groupValidation->id] ?? null;

                    if (! $row) {
                        return;
                    }

                    $groupValidation->update([
                        'mission_id' => $payload['mission_id'],
                        'characters' => $row['characters'],
                        'status' => $row['status'],
                    ]);
                });
        }

        return redirect()->route('admin.validations.index')->with('admin_toast', [
            'title' => 'Déclaration modifiée',
            'text' => 'La validation a bien été enregistrée.',
            'type' => 'success',
        ]);
    }

    public function setStatus(Request $request, MissionValidation $validation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(MissionValidation::STATUSES))],
        ]);

        $validation->update([
            'status' => $validated['status'],
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);
        $freshValidation = $validation->fresh(['mission', 'user']);
        AdminNotification::query()
            ->where('area', 'validations')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        AdminActivity::log(
            'validations',
            'status_updated',
            'Statut validation modifie',
            'Statut passe en '.$freshValidation->statusLabel().'.',
            $freshValidation,
            [
                'status' => $freshValidation->status,
                'mission' => $freshValidation->mission?->title,
                'user' => $freshValidation->user?->name,
            ],
        );

        return back()->with('admin_toast', [
            'title' => 'Statut mis à jour',
            'text' => 'La validation est passée en '.$freshValidation->statusLabel().'.',
            'type' => 'success',
        ]);
    }

    public function destroy(MissionValidation $validation): RedirectResponse
    {
        $validation->delete();

        AdminActivity::log('validations', 'trashed', 'Validation mise en corbeille', 'Declaration deplacee dans la corbeille.', $validation);

        return redirect()->route('admin.validations.index')->with('admin_toast', [
            'title' => 'Validation en corbeille',
            'text' => 'La déclaration a été déplacée dans la corbeille.',
            'type' => 'success',
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['pending', 'validated', 'refused', 'trash', 'restore', 'force_delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        if ($data['action'] === 'force_delete') {
            abort_unless($request->user()?->canForceDeleteInAdminArea('validations'), 403);
        } elseif ($data['action'] === 'trash') {
            abort_unless($request->user()?->canDeleteInAdminArea('validations'), 403);
        }

        $count = 0;

        if (in_array($data['action'], array_keys(MissionValidation::STATUSES), true)) {
            $count = MissionValidation::whereKey($data['ids'])->update([
                'status' => $data['action'],
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()?->id,
            ]);
        } elseif ($data['action'] === 'trash') {
            $validations = MissionValidation::whereKey($data['ids'])->get();
            $validations->each->delete();
            $count = $validations->count();
        } elseif ($data['action'] === 'restore') {
            $validations = MissionValidation::onlyTrashed()->whereKey($data['ids'])->get();
            $validations->each->restore();
            $count = $validations->count();
        } else {
            $validations = MissionValidation::onlyTrashed()->whereKey($data['ids'])->get();
            $validations->each->forceDelete();
            $count = $validations->count();
        }

        return back()->with('admin_toast', [
            'title' => 'Action groupée terminée',
            'text' => $count.' validation(s) traitée(s).',
            'type' => $data['action'] === 'force_delete' ? 'warning' : 'success',
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-validations-trash', [
            'validations' => MissionValidation::onlyTrashed()
                ->with(['mission', 'user'])
                ->latest('deleted_at')
                ->paginate(12),
        ]);
    }

    public function restore(int $validation): RedirectResponse
    {
        $trashedValidation = MissionValidation::onlyTrashed()->findOrFail($validation);
        $trashedValidation->restore();

        AdminActivity::log('validations', 'restored', 'Validation restauree', 'Declaration restauree depuis la corbeille.', $trashedValidation);

        return redirect()->route('admin.validations.trash')->with('admin_toast', [
            'title' => 'Validation restaurée',
            'text' => 'La déclaration est de retour dans la liste.',
            'type' => 'success',
        ]);
    }

    public function forceDelete(int $validation): RedirectResponse
    {
        abort_unless(request()->user()?->canForceDeleteInAdminArea('validations'), 403);

        $trashedValidation = MissionValidation::onlyTrashed()->findOrFail($validation);
        AdminActivity::log('validations', 'force_deleted', 'Validation supprimee definitivement', 'Declaration supprimee depuis la corbeille.', $trashedValidation);
        $trashedValidation->forceDelete();

        return redirect()->route('admin.validations.trash')->with('admin_toast', [
            'title' => 'Validation supprimée',
            'text' => 'La déclaration a été supprimée définitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        abort_unless(request()->user()?->canForceDeleteInAdminArea('validations'), 403);

        $count = MissionValidation::onlyTrashed()->count();
        MissionValidation::onlyTrashed()->forceDelete();
        AdminActivity::log('validations', 'trash_emptied', 'Corbeille validations videe', $count.' validation(s) supprimee(s) definitivement.');

        return redirect()->route('admin.validations.trash')->with('admin_toast', [
            'title' => 'Corbeille vidée',
            'text' => 'Toutes les validations en corbeille ont été supprimées définitivement.',
            'type' => 'warning',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, ?MissionValidation $validation = null): array
    {
        $validated = $request->validate([
            'mission_id' => ['required', 'exists:missions,id'],
            'user_id' => ['required', 'exists:users,id'],
            'characters' => ['required', 'integer', 'between:1,8'],
            'status' => ['nullable', Rule::in(array_keys(MissionValidation::STATUSES))],
        ]);

        return [
            'mission_id' => $validated['mission_id'],
            'user_id' => $validated['user_id'],
            'characters' => $validated['characters'],
            'status' => $validated['status'] ?? $validation?->status ?? MissionValidation::PENDING,
        ];
    }

    /**
     * @return Collection<int, array{user_id: int, characters: int}>
     */
    private function teammatePayload(Request $request): Collection
    {
        $validated = $request->validate([
            'teammate_user_id' => ['nullable', 'array'],
            'teammate_user_id.*' => ['nullable', 'distinct', 'different:user_id', 'exists:users,id'],
            'teammate_characters' => ['nullable', 'array'],
            'teammate_characters.*' => ['nullable', 'integer', 'between:1,8'],
        ]);

        return collect($validated['teammate_user_id'] ?? [])
            ->filter()
            ->map(fn (int|string $userId, int $index): array => [
                'user_id' => (int) $userId,
                'characters' => (int) ($validated['teammate_characters'][$index] ?? 1),
            ])
            ->values();
    }

    /**
     * @return Collection<int, MissionValidation>
     */
    private function linkedValidations(MissionValidation $validation): Collection
    {
        $userIds = collect($validation->teammates ?? [])
            ->pluck('user_id')
            ->filter()
            ->push($validation->user_id)
            ->map(fn (int|string $userId): int => (int) $userId)
            ->unique()
            ->values();

        if ($userIds->count() <= 1) {
            return collect([$validation->loadMissing('user')]);
        }

        return MissionValidation::query()
            ->with('user')
            ->where('mission_id', $validation->mission_id)
            ->whereIn('user_id', $userIds)
            ->when(
                $validation->proof_path,
                fn ($query) => $query->where('proof_path', $validation->proof_path),
                fn ($query) => $query->whereBetween('created_at', [
                    $validation->created_at?->copy()->subSeconds(10) ?? now()->subSeconds(10),
                    $validation->created_at?->copy()->addSeconds(10) ?? now()->addSeconds(10),
                ])
            )
            ->orderByRaw('case when id = ? then 0 else 1 end', [$validation->id])
            ->orderBy('id')
            ->get();
    }
}
