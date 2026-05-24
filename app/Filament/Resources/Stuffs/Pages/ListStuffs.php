<?php

namespace App\Filament\Resources\Stuffs\Pages;

use App\Filament\Resources\Stuffs\StuffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStuffs extends ListRecords
{
    protected static string $resource = StuffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Ajouter'),
        ];
    }
}
