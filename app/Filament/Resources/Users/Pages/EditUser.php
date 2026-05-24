<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\PublicUploadManager;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $record */
        $record = $this->getRecord();

        $data['admin_roles'] = $record->adminRoles();
        $data['remove_avatar'] = false;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $roles = $this->validatedRoles($data['admin_roles'] ?? []);
        $isApproved = (bool) ($data['is_approved'] ?? false);

        if (auth()->user()?->is($record) && ! in_array(AdminAccess::ADMIN, $roles, true)) {
            throw ValidationException::withMessages([
                'data.admin_roles' => 'Tu ne peux pas retirer tes propres droits admin.',
            ]);
        }

        if (auth()->user()?->is($record) && ! $isApproved) {
            throw ValidationException::withMessages([
                'data.is_approved' => 'Tu ne peux pas desactiver ton propre compte.',
            ]);
        }

        if (UserResource::isLastAdmin($record) && ! in_array(AdminAccess::ADMIN, $roles, true)) {
            throw ValidationException::withMessages([
                'data.admin_roles' => 'Impossible de retirer le dernier administrateur.',
            ]);
        }

        if (UserResource::isLastAdmin($record) && ! $isApproved) {
            throw ValidationException::withMessages([
                'data.is_approved' => 'Impossible de desactiver le dernier administrateur.',
            ]);
        }

        $record->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'country' => $data['country'] ?: 'fr',
            'is_approved' => $isApproved,
        ]);

        if (filled($data['password'] ?? null)) {
            $record->password = $data['password'];
        }

        if ($data['remove_avatar'] ?? false) {
            self::deleteAvatar($record->avatar_path);
            $record->avatar_path = null;
        }

        if (($data['avatar_upload'] ?? null) instanceof TemporaryUploadedFile) {
            self::deleteAvatar($record->avatar_path);
            $record->avatar_path = PublicUploadManager::store($data['avatar_upload'], 'avatars', 'avatar');
        }

        $record->setAdminRoles($roles);
        $record->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make()
                ->before(fn (User $record): bool => self::deleteAvatar($record->avatar_path)),
            RestoreAction::make(),
        ];
    }

    /**
     * @param mixed $roles
     * @return list<string>
     */
    private function validatedRoles(mixed $roles): array
    {
        $roles = array_values(array_filter((array) $roles, 'is_string'));

        if ($roles === [] || array_diff($roles, array_keys(AdminAccess::roles())) !== []) {
            throw ValidationException::withMessages([
                'data.admin_roles' => 'Choisis au moins un role valide.',
            ]);
        }

        return $roles;
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
