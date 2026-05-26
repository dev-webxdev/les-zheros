<?php

namespace App\Support;

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminRoleFilamentData
{
    public const COLORS = [
        'primary' => 'Bleu',
        'success' => 'Vert',
        'danger' => 'Rouge',
        'warning' => 'Orange',
        'violet' => 'Violet',
        'teal' => 'Sarcelle',
        'pink' => 'Rose',
        'sky' => 'Ciel',
        'neutral' => 'Neutre',
    ];

    public static function syncDefaultRoles(): void
    {
        foreach (AdminAccess::defaultRoles() as $key => $label) {
            if (AdminRole::withTrashed()->where('key', $key)->exists()) {
                continue;
            }

            AdminRole::create([
                'key' => $key,
                'label' => $label,
                'color' => AdminAccess::roleColor($key),
                'permissions' => AdminAccess::rolePermissions($key),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data, ?AdminRole $role = null): array
    {
        $label = trim((string) ($data['label'] ?? ''));

        if ($label === '') {
            throw ValidationException::withMessages([
                'data.label' => 'Le nom du role est obligatoire.',
            ]);
        }

        $key = $role?->key ?: (Str::slug($label, '_') ?: 'role');

        if (! $role && array_key_exists($key, AdminAccess::roles())) {
            throw ValidationException::withMessages([
                'data.label' => 'Un role avec ce nom existe deja.',
            ]);
        }

        $permissions = $key === AdminAccess::ADMIN
            ? AdminAccess::rolePermissions(AdminAccess::ADMIN)
            : array_values(array_intersect((array) ($data['permissions'] ?? []), array_keys(AdminAccess::permissions())));

        return [
            'key' => $key,
            'label' => $label,
            'color' => $data['color'] ?? 'neutral',
            'permissions' => $permissions,
        ];
    }

    public static function userCount(string $role): int
    {
        return User::query()
            ->get()
            ->filter(fn (User $user): bool => in_array($role, $user->adminRoles(), true))
            ->count();
    }

    public static function isDefaultRole(string $role): bool
    {
        return array_key_exists($role, AdminAccess::defaultRoles());
    }

    public static function isProtected(AdminRole $role): bool
    {
        return $role->key === AdminAccess::ADMIN
            || self::isDefaultRole($role->key)
            || self::userCount($role->key) > 0;
    }

    /**
     * @param iterable<AdminRole> $roles
     * @return Collection<int, AdminRole>
     */
    public static function protectedRoles(iterable $roles): Collection
    {
        return collect($roles)
            ->filter(fn (AdminRole $role): bool => self::isProtected($role))
            ->values();
    }
}
