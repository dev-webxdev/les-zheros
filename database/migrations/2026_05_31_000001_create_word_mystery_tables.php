<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('word_mystery_words', function (Blueprint $table): void {
            $table->id();
            $table->string('word');
            $table->string('hint');
            $table->string('difficulty');
            $table->unsignedInteger('reward_base');
            $table->date('active_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['difficulty', 'active_date', 'is_active']);
        });

        Schema::create('word_mystery_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained('word_mystery_words')->cascadeOnDelete();
            $table->string('difficulty');
            $table->unsignedTinyInteger('attempts_count')->default(0);
            $table->json('guesses')->nullable();
            $table->boolean('has_won')->default(false);
            $table->unsignedInteger('reward_earned')->default(0);
            $table->timestamp('played_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'word_id', 'difficulty']);
            $table->index(['user_id', 'played_at']);
        });

        Schema::create('word_mystery_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_attempt_id')->constrained('word_mystery_attempts')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique('game_attempt_id');
            $table->index(['user_id', 'status']);
        });

        DB::table('word_mystery_words')->insert([
            [
                'word' => 'Dofus',
                'hint' => 'Un objet legendaire qui donne son nom au monde des Douze.',
                'difficulty' => 'easy',
                'reward_base' => 10000,
                'active_date' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'word' => 'Pandala',
                'hint' => 'Ile connue pour ses villages elementaires et ses esprits.',
                'difficulty' => 'normal',
                'reward_base' => 25000,
                'active_date' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'word' => 'Koutoulou',
                'hint' => 'Gardien abyssal qui aime les combats qui derapent.',
                'difficulty' => 'hard',
                'reward_base' => 50000,
                'active_date' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('word_mystery_rewards');
        Schema::dropIfExists('word_mystery_attempts');
        Schema::dropIfExists('word_mystery_words');
    }
};
