<?php

namespace App\Filament\Resources\MissionValidations\Pages;

use App\Filament\Resources\MissionValidations\MissionValidationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMissionValidations extends ListRecords
{
    protected static string $resource = MissionValidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Ajouter une declaration'),
        ];
    }
}
