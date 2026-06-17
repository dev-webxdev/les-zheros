<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WordMysteryAttempt;
use App\Models\WordMysteryWord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WordMysteryDailyLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_losing_one_difficulty_blocks_other_difficulties_for_the_day(): void
    {
        $user = User::factory()->create();
        $normal = WordMysteryWord::create([
            'word' => 'Bouclier',
            'hint' => 'Protection',
            'difficulty' => 'hard',
            'reward_base' => 50000,
            'active_date' => today(),
            'is_active' => true,
        ]);
        $easy = WordMysteryWord::create([
            'word' => 'Dofus',
            'hint' => 'Oeuf',
            'difficulty' => 'normal',
            'reward_base' => 25000,
            'active_date' => today(),
            'is_active' => true,
        ]);

        WordMysteryAttempt::create([
            'user_id' => $user->id,
            'word_id' => $normal->id,
            'difficulty' => $normal->difficulty,
            'attempts_count' => 6,
            'guesses' => [],
            'has_won' => false,
            'played_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('mot-mystere.submit'), [
                'difficulty' => $easy->difficulty,
                'guess' => 'Dofus',
            ])
            ->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'title' => 'Partie du jour terminee',
            ]);
    }

    public function test_player_can_continue_same_difficulty_until_six_attempts(): void
    {
        $user = User::factory()->create();
        $word = WordMysteryWord::create([
            'word' => 'Dofus',
            'hint' => 'Oeuf',
            'difficulty' => 'normal',
            'reward_base' => 25000,
            'active_date' => today(),
            'is_active' => true,
        ]);

        WordMysteryAttempt::create([
            'user_id' => $user->id,
            'word_id' => $word->id,
            'difficulty' => $word->difficulty,
            'attempts_count' => 5,
            'guesses' => [],
            'has_won' => false,
            'played_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('mot-mystere.submit'), [
                'difficulty' => $word->difficulty,
                'guess' => 'Pious',
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'has_lost' => true,
                'daily_completed' => true,
            ]);
    }
}
