<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_cards_are_filtered_by_granular_permissions(): void
    {
        AdminRole::create([
            'key' => 'points_manager',
            'label' => 'Gestion points',
            'color' => 'primary',
            'permissions' => ['settings.points'],
        ]);

        $user = User::factory()->create();
        $user->setAdminRoles(['points_manager']);
        $user->save();

        $this->actingAs($user)
            ->get(route('admin.parametres.index'))
            ->assertOk()
            ->assertSee('Barème de points')
            ->assertDontSee('Maintenance')
            ->assertDontSee('Sauvegardes du site');

        $this->actingAs($user)
            ->patch(route('admin.parametres.maintenance.update'), [
                'maintenance_enabled' => '1',
                'maintenance_message' => 'Maintenance',
            ])
            ->assertForbidden();
    }

    public function test_web_developer_role_can_manage_maintenance_and_backups_only(): void
    {
        AdminRole::create([
            'key' => 'web_developer',
            'label' => 'Développeur web',
            'color' => 'teal',
            'permissions' => ['settings.maintenance', 'settings.backups'],
        ]);

        $user = User::factory()->create();
        $user->setAdminRoles(['web_developer']);
        $user->save();

        $this->actingAs($user)
            ->get(route('admin.parametres.index'))
            ->assertOk()
            ->assertSee('Maintenance')
            ->assertSee('Sauvegardes du site')
            ->assertDontSee('Loterie')
            ->assertDontSee('Barème de points');

        $this->actingAs($user)
            ->patch(route('admin.parametres.lottery.update'), [
                'lottery_prize_1' => 250000,
                'lottery_prize_2' => 150000,
                'lottery_prize_3' => 100000,
                'lottery_min_points' => 0,
            ])
            ->assertForbidden();
    }
}
