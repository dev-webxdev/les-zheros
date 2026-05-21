<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stuffs', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('class_slug', 40);
            $table->string('class_label', 80);
            $table->json('elements')->nullable();
            $table->string('mode', 40);
            $table->unsignedSmallInteger('min_level')->default(200);
            $table->unsignedSmallInteger('max_level')->default(200);
            $table->string('budget')->nullable();
            $table->string('meta')->nullable();
            $table->string('author')->nullable();
            $table->text('description')->nullable();
            $table->string('dofusbook_url');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stuffs');
    }
};
