<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Support\AdminAccess;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'country', 'avatar_path', 'role', 'admin_roles', 'password', 'is_admin', 'is_approved', 'legacy_points_total'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_approved' => 'boolean',
            'legacy_points_total' => 'float',
            'password' => 'hashed',
        ];
    }

    /**
     * @return list<string>
     */
    public function adminRoles(): array
    {
        $role = $this->admin_roles ?: $this->role;

        if (is_string($role) && str_starts_with($role, '[')) {
            $decoded = json_decode($role, true);

            if (is_array($decoded)) {
                $roles = array_values(array_filter($decoded, 'is_string'));
            }
        }

        $roles ??= is_string($role) && $role !== '' ? [$role] : [AdminAccess::MEMBER];

        if ($this->is_admin && ! in_array(AdminAccess::ADMIN, $roles, true)) {
            $roles[] = AdminAccess::ADMIN;
        }

        return array_values(array_unique($roles));
    }

    public function hasAdminRole(string $role): bool
    {
        return in_array($role, $this->adminRoles(), true);
    }

    public function hasAdminAccess(): bool
    {
        return $this->is_admin
            || count(array_diff($this->adminRoles(), [AdminAccess::MEMBER])) > 0;
    }

    public function canAccessAdminArea(string $area): bool
    {
        if ($area === 'activity') {
            return $this->canAccessAdminPermission('activity.view');
        }

        if ($area === 'media') {
            return $this->canAccessAdminPermission('media.manage');
        }

        if ($area === 'notifications') {
            return $this->canAccessAdminPermission('notifications.view');
        }

        foreach ($this->adminRoles() as $role) {
            $areas = AdminAccess::roleAreas()[$role] ?? [];

            if (in_array('*', $areas, true) || in_array($area, $areas, true)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessAdminPermission(string $permission): bool
    {
        foreach ($this->adminRoles() as $role) {
            $permissions = AdminAccess::rolePermissions($role);

            if (in_array($permission, $permissions, true)) {
                return true;
            }

            [$area] = explode('.', $permission, 2);

            if (in_array($area.'.manage', $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function canDeleteInAdminArea(string $area): bool
    {
        return $this->canAccessAdminArea($area)
            && ! $this->hasAdminRole(AdminAccess::MODERATOR)
            || $this->hasAdminRole(AdminAccess::ADMIN);
    }

    public function canForceDeleteInAdminArea(string $area): bool
    {
        return $this->hasAdminRole(AdminAccess::ADMIN);
    }

    public function setAdminRoles(array $roles): void
    {
        $roles = array_values(array_intersect(array_unique($roles), array_keys(AdminAccess::roles())));

        if ($roles === []) {
            $roles = [AdminAccess::MEMBER];
        }

        $this->admin_roles = json_encode($roles);
        $this->role = $roles[0];
        $this->is_admin = in_array(AdminAccess::ADMIN, $roles, true);
    }

    public function adminRolesLabel(): string
    {
        $labels = AdminAccess::roles();

        return collect(AdminAccess::displayRoles($this->adminRoles()))
            ->map(fn (string $role): string => $labels[$role] ?? $role)
            ->join(', ');
    }

    public function initials(): string
    {
        $initials = collect(explode(' ', $this->name))
            ->filter()
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->join('');

        return mb_strtoupper(mb_substr($initials ?: $this->name, 0, 2));
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ?: null;
    }
}
