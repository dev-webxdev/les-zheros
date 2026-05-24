<?php

namespace App\Filament\Resources\Stuffs\Pages;

use App\Filament\Resources\Stuffs\StuffResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStuff extends ViewRecord
{
    protected static string $resource = StuffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
