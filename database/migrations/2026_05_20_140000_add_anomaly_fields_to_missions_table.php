<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table): void {
            $table->string('anomaly_type', 40)->nullable()->after('category');
            $table->unsignedSmallInteger('anomaly_level')->nullable()->after('anomaly_type');
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table): void {
            $table->dropColumn(['anomaly_type', 'anomaly_level']);
        });
    }
};
