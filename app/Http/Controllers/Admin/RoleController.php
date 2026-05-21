<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()->get();
        $tagClasses = AdminAccess::roleTagClasses();
        $totalPermissionCount = count(AdminAccess::permissions());

        $roles = collect(AdminAccess::roles())
            ->map(function (string $label, string $role) use ($users, $tagClasses, $totalPermissionCount): array {
                $permissions = AdminAccess::rolePermissions($role);
                $permissionCount = count($permissions);

                return [
                    'key' => $role,
                    'label' => $label,
                    'tagClass' => $tagClasses[$role] ?? '',
                    'permissionCount' => $permissionCount,
                    'hasFullAccess' => $permissionCount === $totalPermissionCount,
                    'userCount' => $users->filter(fn (User $user): bool => in_array($role, $user->adminRoles(), true))->count(),
                    'deletable' => $role !== AdminAccess::ADMIN,
                ];
            })
            ->values();

        return view('admin.admin-roles', [
            'roles' => $this->paginateCollection($roles, $request),
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-roles-trash', [
            'roles' => AdminRole::onlyTrashed()
                ->latest('deleted_at')
                ->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form($request);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRole($request);
        $key = Str::slug($validated['name'], '_') ?: 'role';

        if (array_key_exists($key, AdminAccess::roles())) {
            return back()->withInput()->with('admin_toast', [
                'title' => 'Rôle déjà existant',
                'text' => 'Un rôle avec ce nom existe déjà.',
                'type' => 'warning',
            ]);
        }

        AdminRole::create([
            'key' => $key,
            'label' => $validated['name'],
            'color' => $validated['color'],
            'permissions' => array_values($validated['permissions'] ?? []),
        ]);

        return redirect()->route('admin.roles.index')->with('admin_toast', [
            'title' => 'Rôle créé',
            'text' => 'Le rôle est maintenant disponible pour les utilisateurs.',
            'type' => 'success',
        ]);
    }

    public function edit(Request $request, string $role): View
    {
        abort_unless(array_key_exists($role, AdminAccess::roles()), 404);

        return $this->form($request, $role);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        abort_unless(array_key_exists($role, AdminAccess::roles()), 404);

        $validated = $this->validateRole($request);
        $permissions = $role === AdminAccess::ADMIN
            ? array_keys(AdminAccess::permissions())
            : array_values($validated['permissions'] ?? []);

        AdminRole::updateOrCreate(
            ['key' => $role],
            [
                'label' => $validated['name'],
                'color' => $validated['color'],
                'permissions' => $permissions,
            ],
        );

        return redirect()->route('admin.roles.index')->with('admin_toast', [
            'title' => 'Rôle modifié',
            'text' => 'Les permissions du rôle ont bien été enregistrées.',
            'type' => 'success',
        ]);
    }

    public function destroy(string $role): RedirectResponse
    {
        abort_if($role === AdminAccess::ADMIN, 404);
        $this->removeRoleFromUsers($role);

        if (array_key_exists($role, AdminAccess::defaultRoles())) {
            $adminRole = AdminRole::withTrashed()->updateOrCreate(
                ['key' => $role],
                [
                    'label' => AdminAccess::defaultRoles()[$role],
                    'color' => $this->colorForRole($role),
                    'permissions' => AdminAccess::rolePermissions($role),
                ],
            );
        } else {
            $adminRole = AdminRole::query()->where('key', $role)->firstOrFail();
        }

        $adminRole->delete();

        return redirect()->route('admin.roles.index')->with('admin_toast', [
            'title' => 'Rôle en corbeille',
            'text' => 'Le rôle a été déplacé dans la corbeille.',
            'type' => 'success',
        ]);
    }

    public function restore(int $role): RedirectResponse
    {
        AdminRole::onlyTrashed()->findOrFail($role)->restore();

        return redirect()->route('admin.roles.trash')->with('admin_toast', [
            'title' => 'Rôle restauré',
            'text' => 'Le rôle est de retour dans la liste.',
            'type' => 'success',
        ]);
    }

    public function forceDelete(int $role): RedirectResponse
    {
        $adminRole = AdminRole::onlyTrashed()->findOrFail($role);
        $this->removeRoleFromUsers($adminRole->key);
        $adminRole->forceDelete();

        return redirect()->route('admin.roles.trash')->with('admin_toast', [
            'title' => 'Rôle supprimé',
            'text' => 'Le rôle a été supprimé définitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        AdminRole::onlyTrashed()->forceDelete();

        return redirect()->route('admin.roles.trash')->with('admin_toast', [
            'title' => 'Corbeille vidée',
            'text' => 'Tous les rôles en corbeille ont été supprimés définitivement.',
            'type' => 'warning',
        ]);
    }

    private function form(Request $request, ?string $role = null): View
    {
        $roles = AdminAccess::roles();
        $permissions = AdminAccess::permissions();
        $selectedPermissionKeys = old('permissions', $role ? AdminAccess::rolePermissions($role) : []);

        return view('admin.admin-role-create', [
            'mode' => $role ? 'edit' : 'create',
            'roleKey' => $role,
            'roleLabel' => old('name', $role ? $roles[$role] : ''),
            'roleColor' => old('color', $this->colorForRole($role)),
            'roleIsLocked' => $role === AdminAccess::ADMIN,
            'permissions' => $permissions,
            'selectedPermissionKeys' => $selectedPermissionKeys,
            'availablePermissions' => collect($permissions)->reject(
                fn (string $label, string $permission): bool => in_array($permission, $selectedPermissionKeys, true),
            ),
        ]);
    }

    private function colorForRole(?string $role): string
    {
        return $role ? AdminAccess::roleColor($role) : 'neutral';
    }

    private function removeRoleFromUsers(string $role): void
    {
        User::query()->get()->each(function (User $user) use ($role): void {
            $roles = array_values(array_filter(
                $user->adminRoles(),
                fn (string $userRole): bool => $userRole !== $role,
            ));

            if ($roles === []) {
                $roles = [AdminAccess::MEMBER];
            }

            $user->setAdminRoles($roles);
            $user->save();
        });
    }

    /**
     * @param Collection<int, array<string, mixed>> $items
     */
    private function paginateCollection(Collection $items, Request $request): LengthAwarePaginator
    {
        $perPage = 12;
        $page = max(1, (int) $request->query('page', 1));

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }

    /**
     * @return array{name: string, color: string, permissions?: list<string>}
     */
    private function validateRole(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['required', Rule::in(['primary', 'success', 'danger', 'warning', 'violet', 'teal', 'pink', 'sky', 'neutral'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', Rule::in(array_keys(AdminAccess::permissions()))],
        ]);
    }
}
