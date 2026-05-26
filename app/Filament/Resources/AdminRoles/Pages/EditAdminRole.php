<?php

namespace App\Filament\Resources\AdminRoles\Pages;

use App\Filament\Resources\AdminRoles\AdminRoleResource;
use App\Models\AdminRole;
use App\Support\AdminRoleFilamentData;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminRole extends EditRecord
{
    protected static string $resource = AdminRoleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AdminRoleFilamentData::normalize($data, $this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (AdminRole $record): bool => AdminRoleResource::canDelete($record)),
            ForceDeleteAction::make()
                ->visible(fn (AdminRole $record): bool => AdminRoleResource::canForceDelete($record)),
            RestoreAction::make(),
        ];
    }
}
