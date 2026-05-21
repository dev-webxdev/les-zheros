<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\AdminRole;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_creates_internal_notification(): void
    {
        $this->post(route('inscription.store'), [
            'name' => 'Nouveau joueur',
            'email' => 'nouveau@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('connexion'));

        $this->assertDatabaseHas('admin_notifications', [
            'area' => 'users',
            'title' => 'Nouvelle inscription',
        ]);
    }

    public function test_mission_declaration_creates_internal_notification(): void
    {
        $user = User::factory()->create(['is_approved' => true]);
        $mission = Mission::create([
            'title' => 'Mission notification',
            'category' => 'donjon',
            'guildatons' => 10,
            'activity_points' => 20,
        ]);

        $this->actingAs($user)
            ->post(route('profil.missions.store'), [
                'mission_name' => $mission->id,
                'mission_characters' => 1,
            ])
            ->assertRedirect(route('profil', ['tab' => 'missions']));

        $this->assertDatabaseHas('admin_notifications', [
            'area' => 'validations',
            'title' => 'Nouvelle validation',
        ]);
    }

    public function test_notifications_page_is_admin_only_by_default(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $moderator = User::factory()->create();
        $moderator->setAdminRoles([AdminAccess::MODERATOR]);
        $moderator->save();

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk();

        $this->actingAs($moderator)
            ->get(route('admin.notifications.index'))
            ->assertForbidden();
    }

    public function test_custom_role_can_receive_notifications_permission(): void
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
            'area' => 'users',
            'title' => 'Notification test',
        ]);

        $this->actingAs($user)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Notification test');
    }

    public function test_admin_can_mark_notifications_read_and_empty_them(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        AdminNotification::create([
            'area' => 'users',
            'title' => 'Notification test',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.notifications.read-all'))
            ->assertRedirect(route('admin.notifications.index'));

        $this->assertNotNull(AdminNotification::first()?->read_at);

        $this->actingAs($admin)
            ->delete(route('admin.notifications.destroy'))
            ->assertRedirect(route('admin.notifications.index'));

        $this->assertDatabaseCount('admin_notifications', 0);
    }

    public function test_validation_notification_is_read_when_validation_is_processed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $mission = Mission::create([
            'title' => 'Mission traitee',
            'category' => 'donjon',
            'guildatons' => 10,
            'activity_points' => 20,
        ]);
        $validation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'characters' => 1,
            'status' => MissionValidation::PENDING,
        ]);
        AdminNotification::create([
            'area' => 'validations',
            'title' => 'Nouvelle validation',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.validations.status', $validation), ['status' => MissionValidation::VALIDATED])
            ->assertRedirect();

        $this->assertNotNull(AdminNotification::where('area', 'validations')->first()?->read_at);
    }

    public function test_user_notification_is_read_when_user_is_approved(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_approved' => false]);
        AdminNotification::create([
            'area' => 'users',
            'title' => 'Nouvelle inscription',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.utilisateurs.approve', $user))
            ->assertRedirect(route('admin.utilisateurs.index'));

        $this->assertNotNull(AdminNotification::where('area', 'users')->first()?->read_at);
    }
}
