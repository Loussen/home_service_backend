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
            // false = demo/fake phone (no SMS OTP); true = real user (SMS when OTP_DRIVER=sms)
            $table->boolean('is_real')->default(false)->after('phone');
        });

        DB::table('users')->update(['is_real' => false]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_real');
        });
    }
};
