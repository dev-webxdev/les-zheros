<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\Outing;
use App\Models\OutingVote;
use App\Models\User;
use App\Support\AdminNotifier;
use App\Support\MissionCycle;
use App\Support\PublicUploadManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(MissionCycle $missionCycle): View
    {
        $missionCycle->sync();
        $user = auth()->user();
        $profileStats = $this->profileStats($user);

        return view('pages.profil', [
            'missions' => Mission::query()
                ->latest()
                ->get(),
            'missionValidations' => MissionValidation::query()
                ->with('mission')
                ->whereBelongsTo(auth()->user())
                ->latest()
                ->get(),
            'teammates' => User::query()
                ->whereKeyNot(auth()->id())
                ->orderBy('name')
                ->get(['id', 'name']),
            'profileStats' => $profileStats,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'country' => ['required', Rule::in(['fr', 'es', 'pt'])],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'remove_avatar' => ['nullable'],
        ], [
            'avatar.image' => 'La photo de profil doit etre une image.',
            'avatar.max' => 'La photo de profil ne doit pas depasser 4 Mo.',
        ]);

        unset($validated['avatar'], $validated['remove_avatar']);

        if ($request->boolean('remove_avatar')) {
            $this->deletePublicUpload($user->avatar_path);
            $validated['avatar_path'] = null;
        }

        if ($request->hasFile('avatar')) {
            $this->deletePublicUpload($user->avatar_path);
            $validated['avatar_path'] = $this->storeAvatar($request->file('avatar'));
        }

        $user->update($validated);

        return back()->with('toast', [
            'title' => 'Profil mis a jour',
            'text' => 'Tes informations ont bien ete enregistrees.',
            'type' => 'success',
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('toast', [
            'title' => 'Mot de passe modifie',
            'text' => 'Ton nouveau mot de passe a bien ete enregistre.',
            'type' => 'success',
        ]);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:4096'],
        ], [
            'avatar.required' => 'Choisis une photo avant d\'envoyer.',
            'avatar.image' => 'La photo de profil doit etre une image.',
            'avatar.max' => 'La photo de profil ne doit pas depasser 4 Mo.',
        ]);

        $user = $request->user();
        $this->deletePublicUpload($user->avatar_path);

        $user->update([
            'avatar_path' => $this->storeAvatar($validated['avatar']),
        ]);

        return response()->json([
            'avatar_url' => $user->avatar_path,
            'message' => 'Photo de profil enregistrée.',
        ]);
    }

    public function storeMissionValidation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mission_name' => ['required', 'exists:missions,id'],
            'mission_characters' => ['required', 'integer', 'between:1,8'],
            'has_teammates' => ['nullable'],
            'teammate_name' => ['nullable', 'array'],
            'teammate_name.*' => ['nullable', 'distinct', 'exists:users,id'],
            'teammate_characters' => ['nullable', 'array'],
            'teammate_characters.*' => ['nullable', 'integer', 'between:1,8'],
            'proof_file' => ['nullable', 'image', 'max:4096'],
        ], [
            'mission_name.required' => 'Choisis une mission avant d\'envoyer ta déclaration.',
            'mission_name.exists' => 'La mission choisie n\'existe plus.',
            'mission_characters.required' => 'Indique le nombre de personnages utilisés.',
            'mission_characters.integer' => 'Le nombre de personnages doit être un nombre.',
            'mission_characters.between' => 'Le nombre de personnages doit être compris entre 1 et 8.',
            'teammate_name.*.distinct' => 'Un coéquipier ne peut être sélectionné qu\'une seule fois.',
            'teammate_name.*.exists' => 'Un des coéquipiers sélectionnés n\'existe plus.',
            'teammate_characters.*.integer' => 'Le nombre de personnages du coéquipier doit être un nombre.',
            'teammate_characters.*.between' => 'Le nombre de personnages du coéquipier doit être compris entre 1 et 8.',
            'proof_file.image' => 'La preuve doit être une image.',
            'proof_file.max' => 'La preuve ne doit pas dépasser 4 Mo.',
        ]);

        $teammates = [];

        if ($request->boolean('has_teammates')) {
            $names = $validated['teammate_name'] ?? [];
            $characters = $validated['teammate_characters'] ?? [];
            $users = User::query()->whereIn('id', array_filter($names))->pluck('name', 'id');

            foreach ($names as $index => $userId) {
                if (! $userId || ! isset($users[$userId])) {
                    continue;
                }

                $teammates[] = [
                    'user_id' => (int) $userId,
                    'name' => $users[$userId],
                    'characters' => (int) ($characters[$index] ?? 1),
                ];
            }
        }

        $proofPath = null;

        if ($request->hasFile('proof_file')) {
            $proofPath = PublicUploadManager::store($request->file('proof_file'), 'validations', 'proof');
        }

        $groupMembers = collect([
            [
                'user_id' => $request->user()->id,
                'name' => $request->user()->name,
                'characters' => (int) $validated['mission_characters'],
            ],
        ])->merge($teammates)->values();

        DB::transaction(function () use ($groupMembers, $proofPath, $validated): void {
            foreach ($groupMembers as $member) {
                MissionValidation::create([
                    'mission_id' => $validated['mission_name'],
                    'user_id' => $member['user_id'],
                    'characters' => $member['characters'],
                    'teammates' => $groupMembers
                        ->reject(fn (array $teammate): bool => $teammate['user_id'] === $member['user_id'])
                        ->values()
                        ->all(),
                    'proof_path' => $proofPath,
                    'status' => MissionValidation::PENDING,
                ]);
            }
        });

        $mission = Mission::find($validated['mission_name']);
        AdminNotifier::notify(
            'validations',
            'Nouvelle validation',
            $request->user()->name.' a envoye une declaration'.($mission ? ' pour '.$mission->title : '').'.',
            route('admin.validations.index'),
            'info',
        );

        return redirect()->route('profil', ['tab' => 'missions'])->with('toast', [
            'title' => 'Déclaration envoyée',
            'text' => 'Ta mission est en attente de validation.',
            'type' => 'success',
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('connexion')->with('toast', [
            'title' => 'Demande envoyee',
            'text' => 'Ton compte a ete place en corbeille. Un admin pourra traiter ou annuler la demande.',
            'type' => 'warning',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function profileStats(?User $user): array
    {
        if (! $user) {
            return [
                'completedOutings' => 0,
                'participationRate' => 0,
                'guildMissions' => 0,
                'missionPoints' => 0,
                'months' => [],
            ];
        }

        $completedOutings = OutingVote::query()
            ->where('user_id', $user->id)
            ->whereHas('outing', function ($query): void {
                $query
                    ->whereNotNull('confirmed_slot_id')
                    ->whereColumn('outings.confirmed_slot_id', 'outing_votes.slot_id');
            })
            ->count();

        $confirmedOutings = Outing::query()
            ->whereNotNull('confirmed_slot_id')
            ->count();

        $validatedMissions = MissionValidation::query()
            ->where('user_id', $user->id)
            ->where('status', MissionValidation::VALIDATED)
            ->get();

        $months = collect(range(5, 0))
            ->map(function (int $offset) use ($user): array {
                $month = CarbonImmutable::now()->startOfMonth()->subMonths($offset);
                $start = $month->startOfMonth();
                $end = $month->endOfMonth();

                return [
                    'label' => $month->translatedFormat('M'),
                    'missions' => MissionValidation::query()
                        ->where('user_id', $user->id)
                        ->where('status', MissionValidation::VALIDATED)
                        ->whereBetween('created_at', [$start, $end])
                        ->count(),
                    'outings' => OutingVote::query()
                        ->where('user_id', $user->id)
                        ->whereHas('outing', function ($query) use ($start, $end): void {
                            $query
                                ->whereNotNull('confirmed_slot_id')
                                ->whereColumn('outings.confirmed_slot_id', 'outing_votes.slot_id')
                                ->whereBetween('confirmed_at', [$start, $end]);
                        })
                        ->count(),
                ];
            })
            ->all();

        return [
            'completedOutings' => $completedOutings,
            'participationRate' => $confirmedOutings > 0 ? (int) round(($completedOutings / $confirmedOutings) * 100) : 0,
            'guildMissions' => $validatedMissions->count(),
            'missionPoints' => round($validatedMissions->sum(fn (MissionValidation $validation): float => $validation->points()), 2),
            'months' => $months,
        ];
    }

    private function storeAvatar($file): string
    {
        return PublicUploadManager::store($file, 'avatars', 'avatar');
    }

    private function deletePublicUpload(?string $path): void
    {
        if (! $path || ! str_contains($path, '/assets/uploads/avatars/')) {
            return;
        }

        $relativePath = parse_url($path, PHP_URL_PATH);

        if (! $relativePath) {
            return;
        }

        File::delete(public_path(ltrim($relativePath, '/')));
    }
}
