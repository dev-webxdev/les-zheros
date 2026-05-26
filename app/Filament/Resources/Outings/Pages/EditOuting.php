<?php

namespace App\Filament\Resources\Outings\Pages;

use App\Filament\Resources\Outings\OutingResource;
use App\Support\OutingFilamentData;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOuting extends EditRecord
{
    protected static string $resource = OutingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return OutingFilamentData::normalize($data, $this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
