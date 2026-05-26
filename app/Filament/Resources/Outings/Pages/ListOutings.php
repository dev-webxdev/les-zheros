<?php

namespace App\Filament\Resources\Outings\Pages;

use App\Filament\Resources\Outings\OutingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOutings extends ListRecords
{
    protected static string $resource = OutingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
