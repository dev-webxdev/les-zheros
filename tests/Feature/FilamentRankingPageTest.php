<?php

namespace Tests\Feature;

use App\Filament\Pages\Ranking;
use App\Models\AdminRole;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentRankingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_ranking_page_uses_existing_ranking_board_points(): void
    {
        $admin = $this->rankingAdmin();
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'donjon',
        ]);
        $adon = User::factory()->create(['name' => 'Adon']);
        $caviar = User::factory()->create(['name' => 'Caviar']);

        MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $adon->id,
            'characters' => 3,
            'teammates' => [['user_id' => $caviar->id, 'name' => 'Caviar', 'characters' => 1]],
            'status' => MissionValidation::VALIDATED,
        ]);

        MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $caviar->id,
            'characters' => 1,
            'status' => MissionValidation::VALIDATED,
        ]);

        $this->actingAs($admin);

        Livewire::test(Ranking::class)
            ->assertSee('Classement')
            ->assertSee('Adon')
            ->assertSee('Caviar')
            ->assertSee('Points mois')
            ->call('sortTable', 'week')
            ->assertSet('tableSort', 'week:asc')
            ->call('sortTable', 'week')
            ->assertSet('tableSort', 'week:desc')
            ->call('sortTable', 'month')
            ->assertSet('tableSort', 'month:desc')
            ->set('tableSearch', 'ado')
            ->assertSee('Adon')
            ->assertDontSee('Caviar');

        Livewire::test(Ranking::class)
            ->call('sortTable', 'month')
            ->assertSet('tableSort', 'month:asc');

        Livewire::test(Ranking::class)
            ->call('sortTable', 'total')
            ->assertSet('tableSort', 'total:asc');
    }

    private function rankingAdmin(): User
    {
        AdminRole::create([
            'key' => 'ranking_manager',
            'label' => 'Gestion classement',
            'color' => 'primary',
            'permissions' => ['ranking.manage'],
        ]);

        $user = User::factory()->create();
        $user->setAdminRoles(['ranking_manager']);
        $user->save();

        return $user;
    }
}
