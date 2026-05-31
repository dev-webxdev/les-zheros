<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next, ?string $area = null, ?string $action = null): Response
    {
        $user = $request->user();
        $area ??= $this->guessAreaFromRouteName((string) $request->route()?->getName());

        if (! $user?->hasAdminAccess() || ($area && ! $user->canAccessAdminArea($area))) {
            abort(403);
        }

        if ($action && ! in_array($action, ['delete', 'force-delete'], true) && ! $user->canAccessAdminPermission($area.'.'.$action)) {
            abort(403);
        }

        if ($action === 'delete' && $area && ! $user->canDeleteInAdminArea($area)) {
            abort(403);
        }

        if ($action === 'force-delete' && $area && ! $user->canForceDeleteInAdminArea($area)) {
            abort(403);
        }

        return $next($request);
    }

    private function guessAreaFromRouteName(string $routeName): ?string
    {
        return match (true) {
            Str::startsWith($routeName, 'admin.missions') => 'missions',
            Str::startsWith($routeName, 'admin.activite') => 'activity',
            Str::startsWith($routeName, 'admin.mediatheque') => 'media',
            Str::startsWith($routeName, 'admin.notifications') => 'notifications',
            Str::startsWith($routeName, 'admin.guides') => 'guides',
            Str::startsWith($routeName, 'admin.galerie') => 'gallery',
            Str::startsWith($routeName, 'admin.annonces') => 'announcements',
            Str::startsWith($routeName, 'admin.commentaires') => 'comments',
            Str::startsWith($routeName, 'admin.loterie') => 'lottery',
            Str::startsWith($routeName, 'admin.classement') => 'ranking',
            Str::startsWith($routeName, 'admin.roles') => 'roles',
            Str::startsWith($routeName, 'admin.parametres') => 'settings',
            Str::startsWith($routeName, 'admin.sorties') => 'outings',
            Str::startsWith($routeName, 'admin.stuffs') => 'stuffs',
            Str::startsWith($routeName, 'admin.utilisateurs') => 'users',
            Str::startsWith($routeName, 'admin.validations') => 'validations',
            Str::startsWith($routeName, 'admin.mot-mystere') => null,
            default => null,
        };
    }
}
