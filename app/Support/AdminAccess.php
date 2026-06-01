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
            'announcements.delete' => 'Mettre en corbeille les annonces',
            'dashboard.view' => 'Voir le dashboard',
            'comments.manage' => 'Ajouter / modifier les commentaires',
            'comments.delete' => 'Mettre en corbeille les commentaires',
            'gallery.manage' => 'Ajouter / modifier les photos',
            'gallery.delete' => 'Mettre en corbeille les photos',
            'guides.manage' => 'Ajouter / modifier les guides',
            'guides.delete' => 'Mettre en corbeille les guides',
            'lottery.manage' => 'Ajouter / modifier la loterie',
            'missions.manage' => 'Ajouter / modifier les missions',
            'missions.delete' => 'Mettre en corbeille les missions',
            'notifications.view' => 'Voir les notifications internes',
            'outings.manage' => 'Ajouter / modifier les sorties',
            'outings.delete' => 'Mettre en corbeille les sorties',
            'ranking.manage' => 'Ajouter / modifier le classement',
            'roles.manage' => 'Ajouter / modifier les rôles',
            'roles.delete' => 'Mettre en corbeille les rôles',
            'settings.manage' => 'Ajouter / modifier tous les paramètres',
            'settings.cycle' => 'Ajouter / modifier la fin du cycle',
            'settings.points' => 'Ajouter / modifier le barème de points',
            'settings.lottery' => 'Ajouter / modifier les paramètres de loterie',
            'settings.maintenance' => 'Gérer la maintenance',
            'settings.backups' => 'Gérer les sauvegardes',
            'settings.word_mystery' => 'Ajouter / modifier les paramètres Mot Mystere',
            'stuffs.manage' => 'Ajouter / modifier les stuffs',
            'stuffs.delete' => 'Mettre en corbeille les stuffs',
            'users.manage' => 'Ajouter / modifier les utilisateurs',
            'users.delete' => 'Mettre en corbeille les utilisateurs',
            'validations.manage' => 'Ajouter / modifier les validations',
            'validations.delete' => 'Mettre en corbeille les validations',
            'word_mystery.manage' => 'Ajouter / modifier Mot Mystere',
            'word_mystery.delete' => 'Mettre en corbeille Mot Mystere',
        ];
    }

    /**
     * @return array<string, array{label: string, icon: string, permissions: list<string>}>
     */
    public static function permissionCategories(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'icon' => 'fa-solid fa-gauge-high',
                'permissions' => ['dashboard.view'],
            ],
            'announcements' => [
                'label' => 'Annonces',
                'icon' => 'fa-solid fa-bullhorn',
                'permissions' => [
                    'announcements.manage',
                    'announcements.delete',
                ],
            ],
            'missions' => [
                'label' => 'Missions',
                'icon' => 'fa-solid fa-scroll',
                'permissions' => [
                    'missions.manage',
                    'missions.delete',
                ],
            ],
            'validations' => [
                'label' => 'Validations',
                'icon' => 'fa-solid fa-circle-check',
                'permissions' => [
                    'validations.manage',
                    'validations.delete',
                ],
            ],
            'guides' => [
                'label' => 'Guides',
                'icon' => 'fa-solid fa-book-open',
                'permissions' => [
                    'guides.manage',
                    'guides.delete',
                ],
            ],
            'gallery' => [
                'label' => 'Galerie',
                'icon' => 'fa-regular fa-images',
                'permissions' => [
                    'gallery.manage',
                    'gallery.delete',
                ],
            ],
            'stuffs' => [
                'label' => 'Catalogue stuffs',
                'icon' => 'fa-solid fa-shield-halved',
                'permissions' => [
                    'stuffs.manage',
                    'stuffs.delete',
                ],
            ],
            'outings' => [
                'label' => 'Sorties',
                'icon' => 'fa-solid fa-users',
                'permissions' => [
                    'outings.manage',
                    'outings.delete',
                ],
            ],
            'lottery' => [
                'label' => 'Loterie',
                'icon' => 'fa-solid fa-dice',
                'permissions' => [
                    'lottery.manage',
                ],
            ],
            'word_mystery' => [
                'label' => 'Mot Mystere',
                'icon' => 'fa-solid fa-key',
                'permissions' => [
                    'word_mystery.manage',
                    'word_mystery.delete',
                ],
            ],
            'ranking' => [
                'label' => 'Classement',
                'icon' => 'fa-solid fa-trophy',
                'permissions' => [
                    'ranking.manage',
                ],
            ],
            'users' => [
                'label' => 'Utilisateurs',
                'icon' => 'fa-solid fa-users',
                'permissions' => [
                    'users.manage',
                    'users.delete',
                ],
            ],
            'comments' => [
                'label' => 'Commentaires',
                'icon' => 'fa-solid fa-comments',
                'permissions' => [
                    'comments.manage',
                    'comments.delete',
                ],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'icon' => 'fa-solid fa-bell',
                'permissions' => [
                    'notifications.view',
                ],
            ],
            'activity' => [
                'label' => 'Activite',
                'icon' => 'fa-solid fa-clock-rotate-left',
                'permissions' => [
                    'activity.view',
                ],
            ],
            'roles' => [
                'label' => 'Roles',
                'icon' => 'fa-solid fa-user-shield',
                'permissions' => [
                    'roles.manage',
                    'roles.delete',
                ],
            ],
            'settings' => [
                'label' => 'Parametres',
                'icon' => 'fa-solid fa-gears',
                'permissions' => [
                    'settings.manage',
                    'settings.cycle',
                    'settings.points',
                    'settings.lottery',
                    'settings.word_mystery',
                    'settings.maintenance',
                    'settings.backups',
                ],
            ],
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
            if ($role['key'] === self::ADMIN) {
                continue;
            }

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
                'dashboard',
                'gallery',
                'guides',
                'lottery',
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
