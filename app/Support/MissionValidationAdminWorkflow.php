<?php

namespace App\Support;

use App\Models\AdminNotification;
use App\Models\MissionValidation;
use App\Models\User;

class MissionValidationAdminWorkflow
{
    public static function setStatus(MissionValidation $validation, string $status, ?User $actor = null): bool
    {
        $validation->update([
            'status' => $status,
            'reviewed_at' => now(),
            'reviewed_by' => $actor?->id,
        ]);

        $freshValidation = $validation->fresh(['mission', 'user']);

        self::markValidationNotificationsAsRead();
        self::logStatusUpdated($freshValidation);

        return true;
    }

    public static function markValidationNotificationsAsRead(): void
    {
        AdminNotification::query()
            ->where('area', 'validations')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function logCreated(): void
    {
        AdminActivity::log('validations', 'created', 'Validation ajoutee', 'Declaration ajoutee depuis Filament.');
    }

    public static function logUpdated(MissionValidation $validation): void
    {
        AdminActivity::log('validations', 'updated', 'Validation modifiee', 'Declaration mise a jour depuis Filament.', $validation);
    }

    public static function logStatusUpdated(MissionValidation $validation): void
    {
        AdminActivity::log(
            'validations',
            'status_updated',
            'Statut validation modifie',
            'Statut passe en '.$validation->statusLabel().'.',
            $validation,
            [
                'status' => $validation->status,
                'mission' => $validation->mission?->title,
                'user' => $validation->user?->name,
            ],
        );
    }

    public static function logTrashed(MissionValidation $validation): void
    {
        AdminActivity::log('validations', 'trashed', 'Validation mise en corbeille', 'Declaration deplacee dans la corbeille depuis Filament.', $validation);
    }

    public static function logRestored(MissionValidation $validation): void
    {
        AdminActivity::log('validations', 'restored', 'Validation restauree', 'Declaration restauree depuis Filament.', $validation);
    }

    public static function logForceDeleted(MissionValidation $validation): void
    {
        AdminActivity::log('validations', 'force_deleted', 'Validation supprimee definitivement', 'Declaration supprimee definitivement depuis Filament.', $validation);
    }
}
