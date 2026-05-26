<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Models\AdminRole;
use App\Models\GuildSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_settings_page_updates_authorized_point_settings_only(): void
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

        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->assertSee('Bareme de points')
            ->assertDontSee('Maintenance')
            ->set('data.'.GuildSetting::MISSION_POINTS_BASE, 3)
            ->set('data.'.GuildSetting::MISSION_BONUS_PER_EXTRA_CHARACTER, 0.5)
            ->set('data.'.GuildSetting::GUILD_HELP_POINTS, 1)
            ->call('save');

        $this->assertDatabaseHas('guild_settings', [
            'key' => GuildSetting::MISSION_POINTS_BASE,
            'value' => '3',
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'area' => 'settings',
            'action' => 'points_updated',
        ]);
        $this->assertDatabaseMissing('guild_settings', [
            'key' => GuildSetting::MAINTENANCE_ENABLED,
            'value' => '1',
        ]);
    }

    public function test_filament_settings_page_updates_maintenance_for_authorized_role(): void
    {
        AdminRole::create([
            'key' => 'web_developer',
            'label' => 'Developpeur web',
            'color' => 'teal',
            'permissions' => ['settings.maintenance'],
        ]);

        $user = User::factory()->create();
        $user->setAdminRoles(['web_developer']);
        $user->save();

        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->assertSee('Maintenance')
            ->assertDontSee('Loterie')
            ->set('data.'.GuildSetting::MAINTENANCE_ENABLED, true)
            ->set('data.'.GuildSetting::MAINTENANCE_MESSAGE, 'Maintenance test')
            ->call('save');

        $this->assertDatabaseHas('guild_settings', [
            'key' => GuildSetting::MAINTENANCE_ENABLED,
            'value' => '1',
        ]);
        $this->assertDatabaseHas('guild_settings', [
            'key' => GuildSetting::MAINTENANCE_MESSAGE,
            'value' => 'Maintenance test',
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'area' => 'settings',
            'action' => 'maintenance_updated',
        ]);
    }
}
