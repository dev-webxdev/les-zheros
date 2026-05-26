<?php

namespace Tests\Feature;

use App\Filament\Resources\AdminNotifications\Pages\ListAdminNotifications;
use App\Filament\Resources\AdminNotifications\Tables\AdminNotificationsTable;
use App\Models\AdminNotification;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAdminNotificationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_consult_notifications_in_filament(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'role' => 'admin',
            'admin_roles' => json_encode(['admin']),
        ]);

        $notification = AdminNotification::create([
            'area' => 'users',
            'type' => 'info',
            'title' => 'Nouvelle inscription',
            'message' => 'Un compte attend validation.',
            'url' => '/admin/utilisateurs',
        ]);

        $this->actingAs($admin)
            ->get('/admin-filament/notifications')
            ->assertOk()
            ->assertSee('Nouvelle inscription')
            ->assertSee('Users');

        $this->actingAs($admin);

        $this->assertStringEndsWith(
            '/admin-filament/users',
            AdminNotificationsTable::targetUrl($notification),
        );
    }

    public function test_filament_notification_links_open_the_related_module_index(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'role' => 'admin',
            'admin_roles' => json_encode(['admin']),
        ]);

        $validationNotification = AdminNotification::create([
            'area' => 'validations',
            'title' => 'Nouvelle validation',
            'url' => 'http://localhost:8000/admin/validations',
        ]);

        $this->actingAs($admin);

        $this->assertStringEndsWith(
            '/admin-filament/mission-validations',
            AdminNotificationsTable::targetUrl($validationNotification),
        );
    }

    public function test_custom_role_with_notification_permission_can_consult_filament_notifications(): void
    {
        AdminRole::create([
            'key' => 'notification_reader',
            'label' => 'Lecteur notifications',
            'color' => 'primary',
            'permissions' => ['notifications.view'],
        ]);
        $user = User::factory()->create();
        $user->setAdminRoles(['notification_reader']);
        $user->save();

        AdminNotification::create([
            'area' => 'validations',
            'title' => 'Nouvelle validation',
        ]);

        $this->actingAs($user)
            ->get('/admin-filament/notifications')
            ->assertOk()
            ->assertSee('Nouvelle validation');
    }

    public function test_admin_can_mark_notification_read_and_delete_it_from_filament(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $notification = AdminNotification::create([
            'area' => 'users',
            'title' => 'Notification test',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListAdminNotifications::class)
            ->callTableAction('markRead', $notification);

        $this->assertNotNull($notification->fresh()?->read_at);

        Livewire::test(ListAdminNotifications::class)
            ->callTableAction('markUnread', $notification->fresh());

        $this->assertNull($notification->fresh()?->read_at);

        Livewire::test(ListAdminNotifications::class)
            ->callTableAction('delete', $notification->fresh());

        $this->assertDatabaseMissing('admin_notifications', [
            'id' => $notification->id,
        ]);
    }
}
