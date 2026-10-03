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
            $table->boolean('has_client_role')->default(false)->after('active_role');
            $table->boolean('has_provider_role')->default(false)->after('has_client_role');
        });

        // Existing locked users keep their single enabled role.
        DB::table('users')
            ->whereNotNull('role_chosen_at')
            ->where('active_role', 'client')
            ->update(['has_client_role' => true]);

        DB::table('users')
            ->whereNotNull('role_chosen_at')
            ->where('active_role', 'provider')
            ->update(['has_provider_role' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['has_client_role', 'has_provider_role']);
        });
    }
};
