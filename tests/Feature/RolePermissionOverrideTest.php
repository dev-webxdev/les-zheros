<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_non_admin_role_permissions_can_be_customized(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.roles.update', AdminAccess::MODERATOR), [
                'name' => 'Modérateur',
                'color' => 'primary',
                'permissions' => ['settings.points', 'settings.lottery'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertSame(
            ['settings.points', 'settings.lottery'],
            AdminAccess::rolePermissions(AdminAccess::MODERATOR),
        );
    }

    public function test_administrator_permissions_are_locked_without_developer_only_access(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.roles.edit', AdminAccess::ADMIN))
            ->assertOk()
            ->assertSee('Accès administrateur verrouillé')
            ->assertDontSee('data-permission-board', false);

        $this->actingAs($admin)
            ->patch(route('admin.roles.update', AdminAccess::ADMIN), [
                'name' => 'Administrateur',
                'color' => 'danger',
                'permissions' => ['settings.points'],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertContains('settings.points', AdminAccess::rolePermissions(AdminAccess::ADMIN));
        $this->assertContains('settings.lottery', AdminAccess::rolePermissions(AdminAccess::ADMIN));
        $this->assertNotContains('settings.maintenance', AdminAccess::rolePermissions(AdminAccess::ADMIN));
        $this->assertNotContains('settings.backups', AdminAccess::rolePermissions(AdminAccess::ADMIN));

        $this->actingAs($admin)
            ->get(route('admin.parametres.index'))
            ->assertOk()
            ->assertSee('Barème de points')
            ->assertSee('Loterie')
            ->assertDontSee('Maintenance')
            ->assertDontSee('Sauvegardes du site');

        $this->actingAs($admin)
            ->patch(route('admin.parametres.maintenance.update'), [
                'maintenance_enabled' => '1',
                'maintenance_message' => 'Maintenance',
            ])
            ->assertForbidden();
    }
}
