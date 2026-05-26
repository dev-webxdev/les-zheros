<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_draws', function (Blueprint $table): void {
            $table->id();
            $table->string('cycle_value');
            $table->string('cycle_label');
            $table->timestamp('drawn_at');
            $table->foreignId('drawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('drawn_by_name')->nullable();
            $table->json('settings');
            $table->json('participants');
            $table->json('winners');
            $table->unsignedInteger('total_tickets')->default(0);
            $table->decimal('total_points', 10, 2)->default(0);
            $table->unsignedBigInteger('total_prize')->default(0);
            $table->timestamps();

            $table->index('cycle_value');
            $table->index('drawn_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_draws');
    }
};
