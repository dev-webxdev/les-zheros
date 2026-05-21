<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mission_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 40);
            $table->text('summary')->nullable();
            $table->json('chips')->nullable();
            $table->json('checklist')->nullable();
            $table->json('sections')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('map_path')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
