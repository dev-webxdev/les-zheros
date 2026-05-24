<?php

namespace App\Filament\Resources\MissionValidations\Pages;

use App\Filament\Resources\MissionValidations\MissionValidationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMissionValidation extends ViewRecord
{
    protected static string $resource = MissionValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
