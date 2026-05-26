<?php

namespace App\Filament\Resources\MissionValidations\Pages;

use App\Filament\Resources\MissionValidations\MissionValidationResource;
use App\Models\MissionValidation;
use App\Models\User;
use App\Support\MissionValidationAdminWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\CreateRecord;

class CreateMissionValidation extends CreateRecord
{
    protected static string $resource = MissionValidationResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['status'] ??= MissionValidation::VALIDATED;

        $payload = [
            'mission_id' => $data['mission_id'],
            'user_id' => (int) $data['user_id'],
            'characters' => (int) $data['characters'],
            'status' => $data['status'],
        ];

        if (in_array($data['status'], [MissionValidation::VALIDATED, MissionValidation::REFUSED], true)) {
            $payload['reviewed_at'] = now();
            $payload['reviewed_by'] = auth()->id();
        }

        $teammates = $this->teammatesFromForm($data, $payload['user_id']);
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
            'name' => (string) ($players[$teammate['user_id']] ?? 'Coequipier'),
            'characters' => $teammate['characters'],
        ]))->values();

        $created = DB::transaction(fn (): Collection => $groupMembers->map(fn (array $member): MissionValidation => MissionValidation::create([
                ...$payload,
                'user_id' => $member['user_id'],
                'characters' => $member['characters'],
                'teammates' => $groupMembers
                    ->reject(fn (array $teammate): bool => $teammate['user_id'] === $member['user_id'])
                    ->values()
                    ->all(),
            ])));

        MissionValidationAdminWorkflow::logCreated();

        return $created->first();
    }

    /**
     * @return Collection<int, array{user_id: int, characters: int}>
     */
    private function teammatesFromForm(array $data, int $mainUserId): Collection
    {
        $teammates = collect($data['teammates'] ?? [])
            ->filter(fn (array $row): bool => filled($row['user_id'] ?? null))
            ->map(fn (array $row): array => [
                'user_id' => (int) $row['user_id'],
                'characters' => (int) ($row['characters'] ?? 1),
            ])
            ->values();

        if ($teammates->pluck('user_id')->contains($mainUserId)) {
            throw ValidationException::withMessages([
                'data.teammates' => 'Un coequipier ne peut pas etre le joueur principal.',
            ]);
        }

        if ($teammates->pluck('user_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'data.teammates' => 'Le meme coequipier ne peut pas etre ajoute plusieurs fois.',
            ]);
        }

        if ($teammates->contains(fn (array $row): bool => $row['characters'] < 1 || $row['characters'] > 8)) {
            throw ValidationException::withMessages([
                'data.teammates' => 'Les personnages des coequipiers doivent etre entre 1 et 8.',
            ]);
        }

        $existingCount = User::query()
            ->whereIn('id', $teammates->pluck('user_id'))
            ->count();

        if ($existingCount !== $teammates->count()) {
            throw ValidationException::withMessages([
                'data.teammates' => 'Un coequipier selectionne est introuvable.',
            ]);
        }

        return $teammates;
    }
}
