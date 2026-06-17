<?php

namespace App\Providers;

use App\Models\AdminNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin', function ($view): void {
            $notificationCount = Schema::hasTable('admin_notifications')
                ? AdminNotification::query()
                    ->whereIn('area', ['users', 'lottery'])
                    ->whereNull('read_at')
                    ->count()
                : 0;

            $view->with([
                'adminUnreadNotificationCount' => $notificationCount,
            ]);
        });

        View::share('versionedAsset', static function (string $path): string {
            $publicPath = public_path($path);

            return asset($path).(is_file($publicPath) ? '?v='.filemtime($publicPath) : '');
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return rtrim((string) config('app.url'), '/').route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = rtrim((string) config('app.url'), '/').route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false);

            return (new MailMessage)
                ->subject('Réinitialisation de ton mot de passe')
                ->greeting('Bonjour,')
                ->line('Tu reçois cet email parce qu’une demande de réinitialisation de mot de passe a été faite pour ton compte Les Zheros.')
                ->action('Réinitialiser mon mot de passe', $url)
                ->line('Ce lien est disponible pendant 10 minutes maximum.')
                ->line('Si tu n’es pas à l’origine de cette demande, tu peux ignorer cet email.')
                ->salutation('À bientôt, l’équipe Les Zheros');
        });
    }
}
