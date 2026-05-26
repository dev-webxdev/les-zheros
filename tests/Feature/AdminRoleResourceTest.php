<?php

namespace Tests\Feature;

use App\Filament\Resources\AdminRoles\Pages\ListAdminRoles;
use App\Models\AdminRole;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\AdminRoleFilamentData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminRoleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_default_role_cannot_be_deleted_by_filament_bulk_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        AdminRoleFilamentData::syncDefaultRoles();

        $role = AdminRole::where('key', AdminAccess::MODERATOR)->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(ListAdminRoles::class)
            ->callTableBulkAction('delete', [$role]);

        $this->assertDatabaseHas('admin_roles', [
            'id' => $role->id,
            'deleted_at' => null,
        ]);
    }

    public function test_role_used_by_user_cannot_be_deleted_by_filament_bulk_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = AdminRole::create([
            'key' => 'event_manager',
            'label' => 'Gestion sorties',
            'color' => 'primary',
            'permissions' => ['outings.manage'],
        ]);
        $user = User::factory()->create();
        $user->setAdminRoles([$role->key]);
        $user->save();

        $this->actingAs($admin);

        Livewire::test(ListAdminRoles::class)
            ->callTableBulkAction('delete', [$role]);

        $this->assertDatabaseHas('admin_roles', [
            'id' => $role->id,
            'deleted_at' => null,
        ]);
    }

    public function test_unprotected_custom_role_can_be_deleted_by_filament_bulk_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $role = AdminRole::create([
            'key' => 'temporary_role',
            'label' => 'Role temporaire',
            'color' => 'neutral',
            'permissions' => ['guides.manage'],
        ]);

        $this->actingAs($admin);

        Livewire::test(ListAdminRoles::class)
            ->callTableBulkAction('delete', [$role]);

        $this->assertSoftDeleted('admin_roles', [
            'id' => $role->id,
        ]);
    }

    public function test_protected_role_cannot_be_force_deleted_by_filament_bulk_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        AdminRoleFilamentData::syncDefaultRoles();

        $role = AdminRole::where('key', AdminAccess::MODERATOR)->firstOrFail();
        $role->delete();
        $trashedRole = AdminRole::withTrashed()->findOrFail($role->id);

        $this->actingAs($admin);

        Livewire::test(ListAdminRoles::class)
            ->filterTable('trashed', true)
            ->callTableBulkAction('forceDelete', [$trashedRole]);

        $this->assertSoftDeleted('admin_roles', [
            'id' => $role->id,
        ]);
    }

    public function test_protected_role_cannot_be_restored_by_filament_bulk_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        AdminRoleFilamentData::syncDefaultRoles();

        $role = AdminRole::where('key', AdminAccess::MODERATOR)->firstOrFail();
        $role->delete();
        $trashedRole = AdminRole::withTrashed()->findOrFail($role->id);

        $this->actingAs($admin);

        Livewire::test(ListAdminRoles::class)
            ->filterTable('trashed', true)
            ->callTableBulkAction('restore', [$trashedRole]);

        $this->assertSoftDeleted('admin_roles', [
            'id' => $role->id,
        ]);
    }
}
