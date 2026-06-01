<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('word_mystery_words', function (Blueprint $table): void {
            $table->json('reward_steps')->nullable()->after('reward_base');
        });
    }

    public function down(): void
    {
        Schema::table('word_mystery_words', function (Blueprint $table): void {
            $table->dropColumn('reward_steps');
        });
    }
};
