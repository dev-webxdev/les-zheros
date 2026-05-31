<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-notifications', [
            'notifications' => AdminNotification::query()
                ->whereIn('area', ['users', 'lottery'])
                ->latest()
                ->paginate(20),
            'unreadCount' => AdminNotification::query()
                ->whereIn('area', ['users', 'lottery'])
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function markAllRead(): RedirectResponse
    {
        AdminNotification::query()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->route('admin.notifications.index')->with('admin_toast', [
            'title' => 'Notifications lues',
            'text' => 'Toutes les notifications ont ete marquees comme lues.',
            'type' => 'success',
        ]);
    }

    public function destroy(): RedirectResponse
    {
        AdminNotification::query()->delete();

        return redirect()->route('admin.notifications.index')->with('admin_toast', [
            'title' => 'Notifications videes',
            'text' => 'Toutes les notifications internes ont ete supprimees.',
            'type' => 'warning',
        ]);
    }
}
