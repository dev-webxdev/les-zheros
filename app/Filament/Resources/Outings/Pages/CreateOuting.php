<?php

namespace App\Filament\Resources\Outings\Pages;

use App\Filament\Resources\Outings\OutingResource;
use App\Support\OutingFilamentData;
use Filament\Resources\Pages\CreateRecord;

class CreateOuting extends CreateRecord
{
    protected static string $resource = OutingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return OutingFilamentData::normalize($data);
    }
}
