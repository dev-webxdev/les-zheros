<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_and_admin_rankings_use_validated_mission_points(): void
    {
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'donjon',
            'guildatons' => 100,
            'activity_points' => 1000,
        ]);
        $adon = User::factory()->create(['name' => 'Adon']);
        $admin = User::factory()->create(['name' => 'Admin', 'is_admin' => true]);

        $adonValidation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $adon->id,
            'characters' => 3,
            'teammates' => [['user_id' => $admin->id, 'name' => 'Admin', 'characters' => 1]],
            'status' => MissionValidation::VALIDATED,
        ]);
        $adonValidation->created_at = now();
        $adonValidation->save();

        $adminValidation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $admin->id,
            'characters' => 1,
            'teammates' => [['user_id' => $adon->id, 'name' => 'Adon', 'characters' => 3]],
            'status' => MissionValidation::VALIDATED,
        ]);
        $adminValidation->created_at = now()->subMonth();
        $adminValidation->save();

        $this->get(route('classement'))
            ->assertOk()
            ->assertSee('Adon')
            ->assertSee('Admin')
            ->assertSee('data-week="2"', false)
            ->assertSee('data-total="2"', false)
            ->assertSee('data-total="1.5"', false);

        $this->actingAs($admin)
            ->get(route('admin.classement.index'))
            ->assertOk()
            ->assertSee('Adon')
            ->assertSee('Admin')
            ->assertSee('Points de la semaine');
    }
}
