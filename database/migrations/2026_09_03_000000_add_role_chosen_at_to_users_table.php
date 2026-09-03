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
            $table->timestamp('role_chosen_at')->nullable()->after('active_role');
        });

        DB::table('users')->whereNull('role_chosen_at')->update([
            'role_chosen_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role_chosen_at');
        });
    }
};
