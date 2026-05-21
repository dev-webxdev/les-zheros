<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\MissionValidation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MissionValidationPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_validated_mission_points_include_character_and_help_bonuses(): void
    {
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);
        $player = User::factory()->create();
        $teammate = User::factory()->create();

        $validation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 4,
            'teammates' => [[
                'user_id' => $teammate->id,
                'name' => $teammate->name,
                'characters' => 3,
            ]],
            'status' => MissionValidation::VALIDATED,
        ]);

        $this->assertSame(2.25, $validation->points());
        $this->assertSame(2.0, $validation->teammatePointRows()[0]['points']);
    }

    public function test_configured_mission_point_values_are_used_by_lottery_participants(): void
    {
        CarbonImmutable::setTestNow('2026-05-20 10:00:00');
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create(['name' => 'Adon']);
        $teammate = User::factory()->create(['name' => 'Yohan']);

        $this->actingAs($admin)
            ->patch(route('admin.parametres.points.update'), [
                'mission_points_base' => 3,
                'mission_bonus_per_extra_character' => 0.5,
                'guild_help_points' => 1,
            ])
            ->assertRedirect(route('admin.parametres.index'));

        $validation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 2,
            'teammates' => [[
                'user_id' => $teammate->id,
                'name' => $teammate->name,
                'characters' => 1,
            ]],
            'status' => MissionValidation::VALIDATED,
        ]);
        $validation->created_at = CarbonImmutable::parse('2026-05-18 18:00:00');
        $validation->save();

        $this->assertSame(4.5, $validation->points());

        $this->actingAs($admin)
            ->get(route('admin.loterie.index'))
            ->assertOk()
            ->assertSee('"points":4.5', false);

        CarbonImmutable::setTestNow();
    }

    public function test_mission_cycle_advances_and_lottery_uses_current_cycle(): void
    {
        CarbonImmutable::setTestNow('2026-05-20 10:00:00');
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create(['name' => 'Adon']);
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.parametres.cycle.update'), [
                'mission_cycle_end' => '2026-05-20T08:00',
            ])
            ->assertRedirect(route('admin.parametres.index'));

        $currentCycleValidation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 1,
            'status' => MissionValidation::VALIDATED,
        ]);
        $currentCycleValidation->created_at = CarbonImmutable::parse('2026-05-20 09:00:00');
        $currentCycleValidation->save();

        $previousCycleValidation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 1,
            'status' => MissionValidation::VALIDATED,
        ]);
        $previousCycleValidation->created_at = CarbonImmutable::parse('2026-05-19 18:00:00');
        $previousCycleValidation->save();

        $this->actingAs($admin)
            ->get(route('admin.loterie.index'))
            ->assertOk()
            ->assertSee('Cycle du 20/05/2026 08:00 au 27/05/2026 08:00')
            ->assertSee('"points":0.25', false)
            ->assertDontSee('"points":0.5', false);

        $this->assertDatabaseHas('guild_settings', [
            'key' => 'mission_cycle_end',
            'value' => '2026-05-27T08:00',
        ]);

        CarbonImmutable::setTestNow();
    }

    public function test_mission_cycle_sync_command_advances_elapsed_cycle(): void
    {
        CarbonImmutable::setTestNow('2026-05-20 10:00:00');

        $this->artisan('missions:sync-cycle')
            ->expectsOutput('Cycle des missions synchronise.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('guild_settings', [
            'key' => 'mission_cycle_end',
            'value' => '2026-05-26T08:00',
        ]);

        CarbonImmutable::setTestNow();
    }

    public function test_repeated_validated_mission_only_counts_help_and_taken_characters(): void
    {
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);
        $player = User::factory()->create();

        MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 1,
            'status' => MissionValidation::VALIDATED,
        ]);

        $repeatValidation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 4,
            'teammates' => [['name' => 'Adon', 'characters' => 1]],
            'status' => MissionValidation::VALIDATED,
        ]);

        $this->assertSame(1.5, $repeatValidation->points());
    }

    public function test_pending_repeat_estimate_uses_reduced_points(): void
    {
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);
        $player = User::factory()->create();

        MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 2,
            'status' => MissionValidation::PENDING,
        ]);

        $repeatValidation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 2,
            'status' => MissionValidation::PENDING,
        ]);

        $this->assertTrue($repeatValidation->isRepeatEstimate());
        $this->assertSame(0.5, $repeatValidation->estimatedPoints());
        $this->assertSame(0.0, $repeatValidation->points());
    }

    public function test_profile_submission_creates_validation_for_each_teammate(): void
    {
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);
        $player = User::factory()->create();
        $teammate = User::factory()->create();

        $this->actingAs($player)
            ->post(route('profil.missions.store'), [
                'mission_name' => $mission->id,
                'mission_characters' => 4,
                'has_teammates' => '1',
                'teammate_name' => [$teammate->id],
                'teammate_characters' => [3],
                'proof_file' => UploadedFile::fake()->image('preuve.jpg'),
            ])
            ->assertRedirect(route('profil', ['tab' => 'missions']));

        $this->assertDatabaseHas('mission_validations', [
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 4,
            'status' => MissionValidation::PENDING,
        ]);
        $this->assertDatabaseHas('mission_validations', [
            'mission_id' => $mission->id,
            'user_id' => $teammate->id,
            'characters' => 3,
            'status' => MissionValidation::PENDING,
        ]);
        $validations = MissionValidation::whereBelongsTo($mission)->get();

        $this->assertCount(2, $validations);

        $validations
            ->pluck('proof_path')
            ->filter()
            ->each(fn (string $path): bool => File::delete(public_path(parse_url($path, PHP_URL_PATH))));
    }

    public function test_profile_submission_does_not_require_a_screenshot(): void
    {
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);
        $player = User::factory()->create();

        $this->actingAs($player)
            ->post(route('profil.missions.store'), [
                'mission_name' => $mission->id,
                'mission_characters' => 1,
            ])
            ->assertRedirect(route('profil', ['tab' => 'missions']));

        $this->assertDatabaseHas('mission_validations', [
            'mission_id' => $mission->id,
            'user_id' => $player->id,
            'characters' => 1,
            'proof_path' => null,
            'status' => MissionValidation::PENDING,
        ]);
    }

    public function test_admin_can_update_linked_help_validations_together(): void
    {
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);
        $teammate = User::factory()->create();
        $proofPath = 'http://localhost/assets/uploads/validations/preuve-test.png';

        $adminValidation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $admin->id,
            'characters' => 2,
            'teammates' => [[
                'user_id' => $teammate->id,
                'name' => $teammate->name,
                'characters' => 4,
            ]],
            'proof_text' => 'ancienne preuve texte',
            'proof_path' => $proofPath,
            'status' => MissionValidation::PENDING,
        ]);
        $teammateValidation = MissionValidation::create([
            'mission_id' => $mission->id,
            'user_id' => $teammate->id,
            'characters' => 4,
            'teammates' => [[
                'user_id' => $admin->id,
                'name' => $admin->name,
                'characters' => 2,
            ]],
            'proof_path' => $proofPath,
            'status' => MissionValidation::PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.validations.update', $adminValidation), [
                'mission_id' => $mission->id,
                'user_id' => $admin->id,
                'characters' => 3,
                'status' => MissionValidation::VALIDATED,
                'proof_text' => 'ne doit pas etre modifie',
                'sync_group' => '1',
                'group_validations' => [
                    $adminValidation->id => [
                        'characters' => 3,
                        'status' => MissionValidation::VALIDATED,
                    ],
                    $teammateValidation->id => [
                        'characters' => 5,
                        'status' => MissionValidation::REFUSED,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.validations.index'));

        $this->assertDatabaseHas('mission_validations', [
            'id' => $adminValidation->id,
            'characters' => 3,
            'status' => MissionValidation::VALIDATED,
            'proof_text' => 'ancienne preuve texte',
        ]);
        $this->assertDatabaseHas('mission_validations', [
            'id' => $teammateValidation->id,
            'characters' => 5,
            'status' => MissionValidation::REFUSED,
        ]);
    }

    public function test_admin_can_create_manual_declaration_with_teammate(): void
    {
        $mission = Mission::create([
            'title' => 'Protozorreur',
            'category' => 'expedition',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);
        $teammate = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.validations.store'), [
                'mission_id' => $mission->id,
                'user_id' => $admin->id,
                'characters' => 2,
                'status' => MissionValidation::PENDING,
                'teammate_user_id' => [$teammate->id],
                'teammate_characters' => [4],
            ])
            ->assertRedirect(route('admin.validations.index'));

        $this->assertDatabaseHas('mission_validations', [
            'mission_id' => $mission->id,
            'user_id' => $admin->id,
            'characters' => 2,
            'status' => MissionValidation::VALIDATED,
        ]);
        $this->assertDatabaseHas('mission_validations', [
            'mission_id' => $mission->id,
            'user_id' => $teammate->id,
            'characters' => 4,
            'status' => MissionValidation::VALIDATED,
        ]);
        $this->assertSame(2, MissionValidation::whereBelongsTo($mission)->count());
    }
}
