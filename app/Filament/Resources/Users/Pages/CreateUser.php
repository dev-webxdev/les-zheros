<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\PublicUploadManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $roles = $this->validatedRoles($data['admin_roles'] ?? []);

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'country' => $data['country'] ?: 'fr',
            'password' => $data['password'],
            'is_approved' => (bool) ($data['is_approved'] ?? true),
        ]);

        if (($data['avatar_upload'] ?? null) instanceof TemporaryUploadedFile) {
            $user->avatar_path = PublicUploadManager::store($data['avatar_upload'], 'avatars', 'avatar');
        }

        $user->setAdminRoles($roles);
        $user->save();

        return $user;
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
}
