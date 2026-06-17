<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WordMysteryAttempt;
use App\Models\WordMysteryReward;
use App\Models\WordMysteryWord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWordMysteryRewardTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reward_totals_include_all_pages_and_exclude_cancelled_rewards(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $player = User::factory()->create(['name' => 'Joueur Total']);

        foreach (range(1, 13) as $index) {
            $word = WordMysteryWord::create([
                'word' => 'Mot'.$index,
                'hint' => 'Indice',
                'difficulty' => 'hard',
                'reward_base' => 50000,
                'active_date' => today()->addDays($index),
                'is_active' => true,
            ]);
            $attempt = WordMysteryAttempt::create([
                'user_id' => $player->id,
                'word_id' => $word->id,
                'difficulty' => $word->difficulty,
                'attempts_count' => 2,
                'guesses' => [],
                'has_won' => true,
                'reward_earned' => 10000,
                'played_at' => now(),
            ]);

            WordMysteryReward::create([
                'user_id' => $player->id,
                'game_attempt_id' => $attempt->id,
                'amount' => 10000,
                'status' => $index === 13 ? 'cancelled' : ($index === 12 ? 'paid' : 'pending'),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.mot-mystere.index'))
            ->assertOk()
            ->assertDontSee('Historique joueurs')
            ->assertSee('Totaux par joueur')
            ->assertSeeInOrder(['<h1>Recompenses</h1>', '<h1>Totaux par joueur</h1>'], false)
            ->assertSee('110 000 kamas')
            ->assertSee('10 000 kamas')
            ->assertSee('120 000 kamas');
    }
}
