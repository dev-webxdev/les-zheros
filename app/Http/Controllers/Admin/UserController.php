<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\User;
use App\Support\AdminActivity;
use App\Support\AdminAccess;
use App\Support\PublicUploadManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-users', [
            'users' => User::query()
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('admin.admin-user-create', [
            'roleOptions' => AdminAccess::roles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:users,name'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(array_keys(AdminAccess::roles()))],
            'password' => ['required', Password::min(6)],
            'avatar' => ['nullable', 'image', 'max:4096'],
        ], [
            'avatar.image' => 'La photo de profil doit etre une image.',
            'avatar.max' => 'La photo de profil ne doit pas depasser 4 Mo.',
        ]);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_approved' => true,
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $this->storeAvatar($request->file('avatar'));
        }

        $user->setAdminRoles($validated['roles']);
        $user->save();

        AdminActivity::log('users', 'created', 'Utilisateur cree', 'Compte cree depuis l’administration.', $user);

        return redirect()->route('admin.utilisateurs.index')->with('admin_toast', [
            'title' => 'Utilisateur cree',
            'text' => 'Le compte a bien ete ajoute.',
            'type' => 'success',
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.admin-user-edit', [
            'user' => $user,
            'roleOptions' => AdminAccess::roles(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(array_keys(AdminAccess::roles()))],
            'password' => ['nullable', Password::min(6)],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'remove_avatar' => ['nullable'],
            'is_approved' => ['nullable'],
        ], [
            'avatar.image' => 'La photo de profil doit etre une image.',
            'avatar.max' => 'La photo de profil ne doit pas depasser 4 Mo.',
        ]);

        if ($request->user()->is($user) && ! in_array(AdminAccess::ADMIN, $validated['roles'], true)) {
            return back()->withInput()->with('admin_toast', [
                'title' => 'Action impossible',
                'text' => 'Tu ne peux pas retirer tes propres droits admin.',
                'type' => 'warning',
            ]);
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_approved' => $request->boolean('is_approved'),
        ]);
        $user->setAdminRoles($validated['roles']);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->boolean('remove_avatar')) {
            $this->deleteAvatar($user->avatar_path);
            $user->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            $this->deleteAvatar($user->avatar_path);
            $user->avatar_path = $this->storeAvatar($request->file('avatar'));
        }

        $user->save();

        AdminActivity::log('users', 'updated', 'Utilisateur modifie', 'Compte utilisateur mis a jour.', $user);

        return redirect()->route('admin.utilisateurs.index')->with('admin_toast', [
            'title' => 'Utilisateur modifie',
            'text' => 'Le compte a bien ete enregistre.',
            'type' => 'success',
        ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()?->canDeleteInAdminArea('users'), 403);

        if ($request->user()->is($user)) {
            return back()->with('admin_toast', [
                'title' => 'Action impossible',
                'text' => 'Tu ne peux pas mettre ton propre compte a la corbeille.',
                'type' => 'warning',
            ]);
        }

        $user->delete();

        AdminActivity::log('users', 'trashed', 'Utilisateur mis en corbeille', 'Compte deplace dans la corbeille.', $user);

        return redirect()->route('admin.utilisateurs.index')->with('admin_toast', [
            'title' => 'Utilisateur en corbeille',
            'text' => 'Le compte a ete deplace dans la corbeille.',
            'type' => 'success',
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        $user->forceFill(['is_approved' => true])->save();
        AdminNotification::query()
            ->where('area', 'users')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        AdminActivity::log('users', 'approved', 'Utilisateur valide', 'Le compte peut maintenant se connecter.', $user);

        return redirect()->route('admin.utilisateurs.index')->with('admin_toast', [
            'title' => 'Utilisateur valide',
            'text' => 'Le compte peut maintenant se connecter.',
            'type' => 'success',
        ]);
    }

    public function trash(): View
    {
        return view('admin.admin-users-trash', [
            'users' => User::onlyTrashed()
                ->latest('deleted_at')
                ->paginate(12),
        ]);
    }

    public function restore(int $user): RedirectResponse
    {
        $trashedUser = User::onlyTrashed()->findOrFail($user);
        $trashedUser->restore();

        AdminActivity::log('users', 'restored', 'Utilisateur restaure', 'Compte restaure depuis la corbeille.', $trashedUser);

        return redirect()->route('admin.utilisateurs.trash')->with('admin_toast', [
            'title' => 'Utilisateur restaure',
            'text' => 'Le compte est de retour dans la liste.',
            'type' => 'success',
        ]);
    }

    public function forceDelete(int $user): RedirectResponse
    {
        abort_unless(request()->user()?->canDeleteInAdminArea('users'), 403);

        $trashedUser = User::onlyTrashed()->findOrFail($user);
        $this->deleteAvatar($trashedUser->avatar_path);
        AdminActivity::log('users', 'force_deleted', 'Utilisateur supprime definitivement', 'Compte supprime depuis la corbeille.', $trashedUser);
        $trashedUser->forceDelete();

        return redirect()->route('admin.utilisateurs.trash')->with('admin_toast', [
            'title' => 'Utilisateur supprime',
            'text' => 'Le compte a ete supprime definitivement.',
            'type' => 'warning',
        ]);
    }

    public function emptyTrash(): RedirectResponse
    {
        abort_unless(request()->user()?->canDeleteInAdminArea('users'), 403);

        $count = User::onlyTrashed()->count();
        User::onlyTrashed()->get()->each(function (User $user): void {
            $this->deleteAvatar($user->avatar_path);
            $user->forceDelete();
        });
        AdminActivity::log('users', 'trash_emptied', 'Corbeille utilisateurs videe', $count.' compte(s) supprime(s) definitivement.');

        return redirect()->route('admin.utilisateurs.trash')->with('admin_toast', [
            'title' => 'Corbeille vidée',
            'text' => 'Tous les utilisateurs en corbeille ont été supprimés définitivement.',
            'type' => 'warning',
        ]);
    }

    private function storeAvatar(UploadedFile $file): string
    {
        return PublicUploadManager::store($file, 'avatars', 'avatar');
    }

    private function deleteAvatar(?string $path): void
    {
        if (! $path || ! str_contains($path, '/assets/uploads/avatars/')) {
            return;
        }

        $relativePath = parse_url($path, PHP_URL_PATH);

        if (! $relativePath) {
            return;
        }

        File::delete(public_path(ltrim($relativePath, '/')));
    }
}
