<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('share_phone')->default(false)->after('phone');
        });

        Schema::table('request_matches', function (Blueprint $table) {
            $table->timestamp('missed_opportunity_notified_at')->nullable()->after('notified');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('share_phone');
        });

        Schema::table('request_matches', function (Blueprint $table) {
            $table->dropColumn('missed_opportunity_notified_at');
        });
    }
};
