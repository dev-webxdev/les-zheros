<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('word_mystery_words', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('word_mystery_rewards', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('word_mystery_rewards', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('word_mystery_words', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
