<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuildSetting;
use App\Support\AdminActivity;
use App\Support\SiteBackupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(SiteBackupManager $backups): View
    {
        return view('admin.admin-settings', [
            'settings' => GuildSetting::values(),
            'backups' => $backups->list(),
        ]);
    }

    public function updateCycle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mission_cycle_end' => ['required', 'date'],
        ]);

        GuildSetting::setMany([
            GuildSetting::MISSION_CYCLE_END => $validated['mission_cycle_end'],
        ]);

        AdminActivity::log('settings', 'cycle_updated', 'Cycle missions modifie', 'Date de fin du cycle mise a jour.');

        return redirect()->route('admin.parametres.index')->with('admin_toast', [
            'title' => 'Cycle enregistré',
            'text' => 'La date de fin des missions a bien été mise à jour.',
            'type' => 'success',
        ]);
    }

    public function updatePoints(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mission_points_base' => ['required', 'numeric', 'min:0'],
            'mission_bonus_per_extra_character' => ['required', 'numeric', 'min:0'],
            'guild_help_points' => ['required', 'numeric', 'min:0'],
        ]);

        GuildSetting::setMany([
            GuildSetting::MISSION_POINTS_BASE => (float) $validated['mission_points_base'],
            GuildSetting::MISSION_BONUS_PER_EXTRA_CHARACTER => (float) $validated['mission_bonus_per_extra_character'],
            GuildSetting::GUILD_HELP_POINTS => (float) $validated['guild_help_points'],
        ]);

        AdminActivity::log('settings', 'points_updated', 'Bareme de points modifie', 'Regles de points mises a jour.');

        return redirect()->route('admin.parametres.index')->with('admin_toast', [
            'title' => 'Barème enregistré',
            'text' => 'Le barème de points est utilisé dans les validations, le classement et la loterie.',
            'type' => 'success',
        ]);
    }

    public function updateLottery(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lottery_prize_1' => ['required', 'integer', 'min:0'],
            'lottery_prize_2' => ['required', 'integer', 'min:0'],
            'lottery_prize_3' => ['required', 'integer', 'min:0'],
            'lottery_min_points' => ['required', 'numeric', 'min:0'],
        ]);

        GuildSetting::setMany([
            GuildSetting::LOTTERY_PRIZE_1 => (int) $validated['lottery_prize_1'],
            GuildSetting::LOTTERY_PRIZE_2 => (int) $validated['lottery_prize_2'],
            GuildSetting::LOTTERY_PRIZE_3 => (int) $validated['lottery_prize_3'],
            GuildSetting::LOTTERY_MIN_POINTS => (float) $validated['lottery_min_points'],
        ]);

        AdminActivity::log('settings', 'lottery_updated', 'Parametres loterie modifies', 'Gains et seuil de loterie mis a jour.');

        return redirect()->route('admin.parametres.index')->with('admin_toast', [
            'title' => 'Loterie enregistrée',
            'text' => 'Le barème de tirage a bien été mis à jour.',
            'type' => 'success',
        ]);
    }

    public function updateWordMystery(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'word_mystery_rewards' => ['required', 'array'],
            'word_mystery_rewards.*' => ['required', 'array'],
            'word_mystery_rewards.*.base' => ['required', 'string', 'max:16', 'regex:/^[0-9 ]+$/'],
            'word_mystery_rewards.*.bonuses' => ['required', 'array'],
            'word_mystery_rewards.*.bonuses.*' => ['required', 'integer', 'min:-100', 'max:500'],
        ]);

        GuildSetting::setMany([
            GuildSetting::WORD_MYSTERY_REWARDS => collect($validated['word_mystery_rewards'])
                ->map(fn (array $settings): array => [
                    'base' => (int) str_replace(' ', '', $settings['base']),
                    'bonuses' => collect($settings['bonuses'])
                        ->map(fn (int|string $bonus): int => (int) $bonus)
                        ->all(),
                ])
                ->all(),
        ]);

        AdminActivity::log('settings', 'word_mystery_rewards_updated', 'Bareme Mot Mystere modifie', 'Gains de base et bonus par essai mis a jour.');

        return redirect()->route('admin.parametres.index')->with('admin_toast', [
            'title' => 'Mot Mystere enregistre',
            'text' => 'Les gains de base et bonus Mot Mystere ont bien ete mis a jour.',
            'type' => 'success',
        ]);
    }

    public function updateMaintenance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'maintenance_enabled' => ['nullable'],
            'maintenance_message' => ['nullable', 'string', 'max:255'],
        ]);

        GuildSetting::setMany([
            GuildSetting::MAINTENANCE_ENABLED => $request->boolean('maintenance_enabled') ? 1 : 0,
            GuildSetting::MAINTENANCE_MESSAGE => $validated['maintenance_message'] ?: GuildSetting::DEFAULTS[GuildSetting::MAINTENANCE_MESSAGE],
        ]);

        AdminActivity::log(
            'settings',
            'maintenance_updated',
            'Maintenance modifiee',
            $request->boolean('maintenance_enabled') ? 'Maintenance activee.' : 'Maintenance desactivee.',
        );

        return redirect()->route('admin.parametres.index')->with('admin_toast', [
            'title' => 'Maintenance enregistrée',
            'text' => $request->boolean('maintenance_enabled') ? 'Le front affiche maintenant la page de maintenance.' : 'Le site est de nouveau accessible.',
            'type' => 'success',
        ]);
    }
}
