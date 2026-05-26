<?php

namespace Tests\Feature;

use App\Filament\Pages\Lottery;
use App\Models\AdminRole;
use App\Models\GuildSetting;
use App\Models\LotteryDraw;
use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentLotteryPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_filament_lottery_page_displays_eligible_participants(): void
    {
        CarbonImmutable::setTestNow('2026-05-20 10:00:00');
        $admin = $this->lotteryAdmin();
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);
        $player = User::factory()->create(['name' => 'Adon']);

        $validation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 1,
            'status' => MissionValidation::VALIDATED,
        ]);
        $validation->created_at = CarbonImmutable::parse('2026-05-20 09:00:00');
        $validation->save();

        $this->actingAs($admin);

        Livewire::test(Lottery::class)
            ->assertSee('Participants de la semaine')
            ->assertSee('Adon')
            ->assertSee('1');
    }

    public function test_filament_lottery_draw_uses_existing_ticket_logic(): void
    {
        CarbonImmutable::setTestNow('2026-05-20 10:00:00');
        $admin = $this->lotteryAdmin();
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);

        foreach (['Adon', 'Yohan', 'Caviar'] as $name) {
            $player = User::factory()->create(['name' => $name]);
            $validation = MissionValidation::create([
                'mission_id' => $mission->id,
                'user_id' => $player->id,
                'characters' => 1,
                'status' => MissionValidation::VALIDATED,
            ]);
            $validation->created_at = CarbonImmutable::parse('2026-05-20 09:00:00');
            $validation->save();
        }

        $this->actingAs($admin);

        Livewire::test(Lottery::class)
            ->call('drawLottery')
            ->assertDispatched('lottery-drawn');

        $draw = LotteryDraw::query()->first();

        $this->assertNotNull($draw);
        $this->assertCount(3, $draw->winners);
        $this->assertCount(3, $draw->participants);
        $this->assertSame(3, $draw->total_tickets);
        $this->assertSame(3.0, $draw->total_points);
        $this->assertSame(500000, $draw->total_prize);
        $this->assertDatabaseHas('admin_activity_logs', [
            'area' => 'lottery',
            'action' => 'drawn',
        ]);
    }

    public function test_filament_lottery_history_is_loaded_from_database_and_can_be_deleted(): void
    {
        CarbonImmutable::setTestNow('2026-05-20 10:00:00');
        $admin = $this->lotteryAdmin();

        $draw = LotteryDraw::create([
            'cycle_value' => '2026-05-20_08-00',
            'cycle_label' => 'Cycle test',
            'drawn_at' => CarbonImmutable::now(),
            'drawn_by' => $admin->id,
            'drawn_by_name' => $admin->name,
            'settings' => ['prizes' => [250000, 150000, 100000], 'multiplier' => 1, 'min_points' => 1],
            'participants' => [['name' => 'Adon', 'points' => 2, 'tickets' => 2, 'missions' => 1, 'helps' => 0]],
            'winners' => [['name' => 'Adon', 'prize' => 250000, 'points' => 2, 'tickets' => 2, 'missions' => 1, 'helps' => 0]],
            'total_tickets' => 2,
            'total_points' => 2,
            'total_prize' => 250000,
        ]);

        $this->actingAs($admin);

        Livewire::test(Lottery::class)
            ->assertSee('Cycle test')
            ->assertSee('Adon')
            ->call('deleteDraw', $draw->id);

        $this->assertDatabaseMissing('lottery_draws', [
            'id' => $draw->id,
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'area' => 'lottery',
            'action' => 'draw_deleted',
        ]);
    }

    private function lotteryAdmin(): User
    {
        AdminRole::create([
            'key' => 'lottery_manager',
            'label' => 'Gestion loterie',
            'color' => 'primary',
            'permissions' => ['lottery.manage'],
        ]);

        GuildSetting::setMany([
            GuildSetting::MISSION_CYCLE_END => '2026-05-27T08:00',
        ]);

        $user = User::factory()->create();
        $user->setAdminRoles(['lottery_manager']);
        $user->save();

        return $user;
    }
}
