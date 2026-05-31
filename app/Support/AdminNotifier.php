<?php

namespace App\Support;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminNotifier
{
    private const ALLOWED_AREAS = [
        'lottery',
        'users',
    ];

    public static function notify(string $area, string $title, ?string $message = null, ?string $url = null, string $type = 'info'): void
    {
        try {
            if (! in_array($area, self::ALLOWED_AREAS, true) || ! Schema::hasTable('admin_notifications')) {
                return;
            }

            AdminNotification::create([
                'area' => $area,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'url' => $url,
            ]);
        } catch (Throwable) {
            // Une notification ne doit jamais bloquer l'action principale.
        }
    }
}
