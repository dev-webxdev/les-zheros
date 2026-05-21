<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outings', function (Blueprint $table): void {
            $table->string('confirmed_slot_id')->nullable()->after('schedule');
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_slot_id');
        });
    }

    public function down(): void
    {
        Schema::table('outings', function (Blueprint $table): void {
            $table->dropColumn(['confirmed_slot_id', 'confirmed_at']);
        });
    }
};
