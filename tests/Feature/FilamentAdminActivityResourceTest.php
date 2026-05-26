<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminActivityResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_consult_activity_log_in_filament(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'is_admin' => true,
            'role' => 'admin',
            'admin_roles' => json_encode(['admin']),
        ]);

        $log = AdminActivityLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'area' => 'users',
            'action' => 'approved',
            'title' => 'Utilisateur valide',
            'description' => 'Le compte peut maintenant se connecter.',
            'subject_label' => 'Caviar Loterie',
            'properties' => ['status' => 'ok'],
        ]);

        $this->actingAs($admin)
            ->get('/admin-filament/activite')
            ->assertOk()
            ->assertSee('Utilisateur valide')
            ->assertSee('Admin')
            ->assertSee('Users');

        $this->actingAs($admin)
            ->get('/admin-filament/activite/'.$log->id)
            ->assertOk()
            ->assertSee('Caviar Loterie')
            ->assertSee('Le compte peut maintenant se connecter.');
    }

    public function test_custom_role_with_activity_permission_can_consult_filament_activity_log(): void
    {
        AdminRole::create([
            'key' => 'activity_reader',
            'label' => 'Lecteur activite',
            'color' => 'primary',
            'permissions' => ['activity.view'],
        ]);

        $user = User::factory()->create([
            'name' => 'Lecteur',
        ]);
        $user->setAdminRoles(['activity_reader']);
        $user->save();

        AdminActivityLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'area' => 'settings',
            'action' => 'updated',
            'title' => 'Parametres modifies',
        ]);

        $this->actingAs($user)
            ->get('/admin-filament/activite')
            ->assertOk()
            ->assertSee('Parametres modifies');
    }
}
