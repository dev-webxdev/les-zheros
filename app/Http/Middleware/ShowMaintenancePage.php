<?php

namespace App\Http\Middleware;

use App\Models\GuildSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowMaintenancePage
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! GuildSetting::maintenanceEnabled()) {
            return $next($request);
        }

        if ($request->is('admin*') || $request->is('connexion') || $request->is('deconnexion') || $request->is('mot-de-passe*')) {
            return $next($request);
        }

        if ($request->user()?->hasAdminAccess()) {
            return $next($request);
        }

        return response()->view('pages.maintenance', [
            'message' => GuildSetting::values()[GuildSetting::MAINTENANCE_MESSAGE],
        ], 503);
    }
}
