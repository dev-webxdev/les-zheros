<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\AdminRole;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approval_is_logged(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_approved' => false]);

        $this->actingAs($admin)
            ->patch(route('admin.utilisateurs.approve', $user))
            ->assertRedirect(route('admin.utilisateurs.index'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $admin->id,
            'area' => 'users',
            'action' => 'approved',
            'subject_id' => $user->id,
            'subject_label' => $user->name,
        ]);
    }

    public function test_validation_status_update_is_logged(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $mission = Mission::create([
            'title' => 'Mission test',
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

        $this->actingAs($admin)
            ->patch(route('admin.validations.status', $validation), ['status' => MissionValidation::VALIDATED])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $admin->id,
            'area' => 'validations',
            'action' => 'status_updated',
            'subject_id' => $validation->id,
        ]);
    }

    public function test_activity_page_is_admin_only_by_default(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $moderator = User::factory()->create();
        $moderator->setAdminRoles([AdminAccess::MODERATOR]);
        $moderator->save();

        $this->actingAs($admin)
            ->get(route('admin.activite.index'))
            ->assertOk();

        $this->actingAs($moderator)
            ->get(route('admin.activite.index'))
            ->assertForbidden();
    }

    public function test_custom_role_can_receive_activity_permission(): void
    {
        AdminRole::create([
            'key' => 'activity_reader',
            'label' => 'Lecteur activite',
            'color' => 'primary',
            'permissions' => ['activity.view'],
        ]);
        $user = User::factory()->create();
        $user->setAdminRoles(['activity_reader']);
        $user->save();
        AdminActivityLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'area' => 'users',
            'action' => 'test',
            'title' => 'Action test',
        ]);

        $this->actingAs($user)
            ->get(route('admin.activite.index'))
            ->assertOk()
            ->assertSee('Action test');
    }

    public function test_admin_can_empty_activity_log(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        AdminActivityLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'area' => 'users',
            'action' => 'test',
            'title' => 'Action test',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.activite.destroy'))
            ->assertRedirect(route('admin.activite.index'));

        $this->assertDatabaseCount('admin_activity_logs', 0);
    }

    public function test_activity_filters_remove_empty_query_values(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.activite.index', ['search' => '', 'area' => 'all']))
            ->assertRedirect(route('admin.activite.index'));
    }

    public function test_activity_area_filter_uses_clean_path(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.activite.index', ['area' => 'users']))
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/activite?area=users')
            ->assertRedirect(route('admin.activite.index', ['area' => 'users']));
    }
}
