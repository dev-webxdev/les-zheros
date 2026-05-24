<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\File;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make()
                ->before(fn (User $record): bool => self::deleteAvatar($record->avatar_path)),
            RestoreAction::make(),
        ];
    }

    private static function deleteAvatar(?string $path): bool
    {
        if (! $path || ! str_contains($path, '/assets/uploads/avatars/')) {
            return true;
        }

        $relativePath = parse_url($path, PHP_URL_PATH);

        if ($relativePath) {
            File::delete(public_path(ltrim($relativePath, '/')));
        }

        return true;
    }
}
