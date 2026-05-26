<?php

namespace App\Filament\Resources\AdminRoles\Pages;

use App\Filament\Resources\AdminRoles\AdminRoleResource;
use App\Support\AdminRoleFilamentData;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminRole extends CreateRecord
{
    protected static string $resource = AdminRoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AdminRoleFilamentData::normalize($data);
    }
}
