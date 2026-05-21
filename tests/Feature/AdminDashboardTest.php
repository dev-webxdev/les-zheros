<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_no_longer_duplicates_pending_notifications(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create(['name' => 'Adon']);
        $mission = Mission::create(['title' => 'Protozorreur', 'category' => 'expedition']);

        MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 1,
            'status' => MissionValidation::PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('A traiter')
            ->assertDontSee('1 mission en attente de validation')
            ->assertDontSee('data-admin-validation-alert', false)
            ->assertDontSee('data-admin-dashboard-alert', false);
    }

    public function test_pending_validation_no_longer_opens_global_alert(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create(['name' => 'Adon']);
        $mission = Mission::create(['title' => 'Protozorreur', 'category' => 'expedition']);

        MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 1,
            'status' => MissionValidation::PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.parametres.index'))
            ->assertOk()
            ->assertDontSee('data-admin-validation-alert', false)
            ->assertDontSee('data-admin-dashboard-alert', false);

        $this->actingAs($admin)
            ->get(route('admin.validations.index'))
            ->assertOk()
            ->assertDontSee('data-admin-validation-alert', false);
    }
}
