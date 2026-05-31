<?php

namespace App\Support;

use App\Models\AdminRole;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AdminAccess
{
    public const ADMIN = 'admin';
    public const MODERATOR = 'moderator';
    public const MISSION_MASTER = 'mission_master';
    public const EDITOR = 'editor';
    public const ILLUSTRATOR = 'illustrator';
    public const MEMBER = 'member';
    private const DEVELOPER_ONLY_PERMISSIONS = [
        'settings.manage',
        'settings.maintenance',
        'settings.backups',
    ];

    /**
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return array_replace(collect(self::defaultRoles())
            ->except(self::deletedDefaultRoleKeys())
            ->all(), collect(self::storedRoles())
            ->mapWithKeys(fn (array $role): array => [$role['key'] => $role['label']])
            ->all());
    }

    /**
     * @return array<string, string>
     */
    public static function defaultRoles(): array
    {
        return [
            self::ADMIN => 'Administrateur',
            self::MODERATOR => 'Modérateur',
            self::MISSION_MASTER => 'Maître de missions',
            self::EDITOR => 'Rédacteur',
            self::ILLUSTRATOR => 'Illustrateur',
            self::MEMBER => 'Membre',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roleTagClasses(): array
    {
        return array_replace(collect([
            self::ADMIN => 'admin-tag--danger',
            self::MODERATOR => 'admin-tag--primary',
            self::MISSION_MASTER => 'admin-tag--warning',
            self::EDITOR => 'admin-tag--success',
            self::ILLUSTRATOR => 'admin-tag--sky',
            self::MEMBER => '',
        ])->except(self::deletedDefaultRoleKeys())->all(), collect(self::storedRoles())
            ->mapWithKeys(fn (array $role): array => [$role['key'] => 'admin-tag--'.$role['color']])
            ->all());
    }

    public static function roleTagClass(string $role): string
    {
        return self::roleTagClasses()[$role] ?? '';
    }

    public static function roleColor(string $role): string
    {
        return self::storedRoles()[$role]['color'] ?? match ($role) {
            self::ADMIN => 'danger',
            self::MODERATOR => 'primary',
            self::MISSION_MASTER => 'warning',
            self::EDITOR => 'success',
            self::ILLUSTRATOR => 'sky',
            default => 'neutral',
        };
    }

    /**
     * @param list<string> $roles
     * @return list<string>
     */
    public static function displayRoles(array $roles): array
    {
        $roles = array_values(array_intersect(array_unique($roles), array_keys(self::roles())));

        if (in_array(self::ADMIN, $roles, true)) {
            return [self::ADMIN];
        }

        return $roles !== [] ? $roles : [self::MEMBER];
    }

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            'activity.view' => 'Voir le journal d’activité',
            'announcements.manage' => 'Ajouter / modifier les annonces',
            'announcements.delete' => 'Supprimer les annonces',
            'comments.manage' => 'Gérer les commentaires',
            'comments.delete' => 'Supprimer les commentaires',
            'gallery.manage' => 'Ajouter / modifier les photos',
            'gallery.delete' => 'Supprimer les photos',
            'guides.manage' => 'Ajouter / modifier les guides',
            'guides.delete' => 'Supprimer les guides',
            'lottery.manage' => 'Gérer la loterie',
            'media.manage' => 'Gérer la médiathèque',
            'missions.manage' => 'Ajouter / modifier les missions',
            'missions.delete' => 'Supprimer les missions',
            'notifications.view' => 'Voir les notifications internes',
            'outings.manage' => 'Ajouter / modifier les sorties',
            'outings.delete' => 'Supprimer les sorties',
            'ranking.manage' => 'Gérer le classement',
            'roles.manage' => 'Gérer les rôles',
            'roles.delete' => 'Supprimer les rôles',
            'settings.manage' => 'Gérer tous les paramètres',
            'settings.cycle' => 'Gérer la fin du cycle',
            'settings.points' => 'Gérer le barème de points',
            'settings.lottery' => 'Gérer les paramètres de loterie',
            'settings.maintenance' => 'Gérer la maintenance',
            'settings.backups' => 'Gérer les sauvegardes',
            'stuffs.manage' => 'Ajouter / modifier les stuffs',
            'stuffs.delete' => 'Supprimer les stuffs',
            'users.manage' => 'Gérer les utilisateurs',
            'users.delete' => 'Supprimer les utilisateurs',
            'validations.manage' => 'Gérer les validations',
            'validations.delete' => 'Supprimer les validations',
            'word_mystery.manage' => 'Gérer Mot Mystère',
        ];
    }

    /**
     * @return list<string>
     */
    public static function rolePermissions(string $role): array
    {
        $allPermissions = array_keys(self::permissions());
        $storedRole = self::storedRoles()[$role] ?? null;

        if ($role === self::ADMIN) {
            return array_values(array_diff($allPermissions, self::DEVELOPER_ONLY_PERMISSIONS));
        }

        if (in_array($role, self::deletedDefaultRoleKeys(), true)) {
            return $storedRole['permissions'] ?? [];
        }

        if ($storedRole) {
            return $storedRole['permissions'];
        }

        $defaultPermissions = match ($role) {
            self::MODERATOR => array_values(array_filter(
                $allPermissions,
                fn (string $permission): bool => ! str_ends_with($permission, '.delete')
                    && $permission !== 'activity.view'
                    && $permission !== 'media.manage'
                    && $permission !== 'notifications.view'
                    && ! in_array($permission, self::DEVELOPER_ONLY_PERMISSIONS, true),
            )),
            self::MISSION_MASTER => ['missions.manage', 'missions.delete'],
            self::EDITOR => ['guides.manage', 'guides.delete'],
            self::ILLUSTRATOR => ['gallery.manage', 'gallery.delete'],
            default => [],
        };

        if ($defaultPermissions !== [] || $role === self::MEMBER) {
            return $defaultPermissions;
        }

        return self::storedRoles()[$role]['permissions'] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function roleAreas(): array
    {
        $areas = collect([
            self::ADMIN => ['*'],
            self::MODERATOR => ['*'],
            self::MISSION_MASTER => ['missions'],
            self::EDITOR => ['guides'],
            self::ILLUSTRATOR => ['gallery'],
            self::MEMBER => [],
        ])->except(self::deletedDefaultRoleKeys())->all();

        foreach (self::storedRoles() as $role) {
            $areas[$role['key']] = self::areasFromPermissions($role['permissions']);
        }

        return $areas;
    }

    /**
     * @param list<string> $permissions
     * @return list<string>
     */
    private static function areasFromPermissions(array $permissions): array
    {
        return collect($permissions)
            ->map(fn (string $permission): string => str($permission)->before('.')->toString())
            ->filter(fn (string $area): bool => in_array($area, [
                'announcements',
                'activity',
                'comments',
                'gallery',
                'guides',
                'lottery',
                'media',
                'missions',
                'notifications',
                'outings',
                'ranking',
                'roles',
                'settings',
                'stuffs',
                'users',
                'validations',
                'word_mystery',
            ], true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{key: string, label: string, color: string, permissions: list<string>}>
     */
    private static function storedRoles(): array
    {
        try {
            if (! Schema::hasTable('admin_roles')) {
                return [];
            }

            return AdminRole::query()
                ->get(['key', 'label', 'color', 'permissions'])
                ->mapWithKeys(fn (AdminRole $role): array => [
                    $role->key => [
                        'key' => $role->key,
                        'label' => $role->label,
                        'color' => $role->color ?: 'neutral',
                        'permissions' => array_values(array_filter($role->permissions ?? [], 'is_string')),
                    ],
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<string>
     */
    private static function deletedDefaultRoleKeys(): array
    {
        try {
            if (! Schema::hasTable('admin_roles') || ! Schema::hasColumn('admin_roles', 'deleted_at')) {
                return [];
            }

            return AdminRole::onlyTrashed()
                ->whereIn('key', array_diff(array_keys(self::defaultRoles()), [self::ADMIN]))
                ->pluck('key')
                ->filter()
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
