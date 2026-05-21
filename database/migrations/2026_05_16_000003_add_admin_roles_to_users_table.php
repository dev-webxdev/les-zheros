<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('admin_roles')->nullable()->after('role');
        });

        DB::table('users')
            ->select(['id', 'role', 'is_admin'])
            ->orderBy('id')
            ->each(function (object $user): void {
                $roles = [$user->is_admin ? 'admin' : ($user->role ?: 'member')];

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['admin_roles' => json_encode(array_values(array_unique($roles)))]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_roles');
        });
    }
};
