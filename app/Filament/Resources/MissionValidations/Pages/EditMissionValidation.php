<?php

namespace App\Filament\Resources\MissionValidations\Pages;

use App\Filament\Resources\MissionValidations\MissionValidationResource;
use App\Models\MissionValidation;
use App\Support\MissionValidationAdminWorkflow;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMissionValidation extends EditRecord
{
    protected static string $resource = MissionValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(fn (MissionValidation $record): null => self::logTrashed($record)),
            ForceDeleteAction::make()
                ->before(fn (MissionValidation $record): null => self::logForceDeleted($record)),
            RestoreAction::make()
                ->after(fn (MissionValidation $record): null => self::logRestored($record)),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $statusChanged = array_key_exists('status', $data) && $data['status'] !== $record->status;

        if ($statusChanged) {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = auth()->id();
        }

        $record->update($data);
        $record = $record->fresh(['mission', 'user']);

        MissionValidationAdminWorkflow::logUpdated($record);

        if ($statusChanged) {
            MissionValidationAdminWorkflow::markValidationNotificationsAsRead();
            MissionValidationAdminWorkflow::logStatusUpdated($record);
        }

        return $record;
    }

    private static function logTrashed(MissionValidation $record): null
    {
        MissionValidationAdminWorkflow::logTrashed($record);

        return null;
    }

    private static function logRestored(MissionValidation $record): null
    {
        MissionValidationAdminWorkflow::logRestored($record);

        return null;
    }

    private static function logForceDeleted(MissionValidation $record): null
    {
        MissionValidationAdminWorkflow::logForceDeleted($record);

        return null;
    }
}
