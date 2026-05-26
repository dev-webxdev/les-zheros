<?php

namespace App\Filament\Resources\AdminRoles\Pages;

use App\Filament\Resources\AdminRoles\AdminRoleResource;
use App\Models\AdminRole;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAdminRole extends ViewRecord
{
    protected static string $resource = AdminRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->visible(fn (AdminRole $record): bool => AdminRoleResource::canDelete($record)),
            ForceDeleteAction::make()
                ->visible(fn (AdminRole $record): bool => AdminRoleResource::canForceDelete($record)),
            RestoreAction::make(),
        ];
    }
}
