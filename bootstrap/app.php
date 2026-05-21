<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\ShowMaintenancePage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ShowMaintenancePage::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('connexion'));
        $middleware->redirectUsersTo(fn () => route('profil'));

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
