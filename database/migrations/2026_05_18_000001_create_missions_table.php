<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('category', 40);
            $table->string('dream_type', 40)->nullable();
            $table->unsignedTinyInteger('dream_floor')->nullable();
            $table->unsignedInteger('guildatons')->nullable();
            $table->unsignedInteger('activity_points')->nullable();
            $table->string('image_mode', 30)->nullable();
            $table->string('image_path')->nullable();
            $table->string('monster_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
