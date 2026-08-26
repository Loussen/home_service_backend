<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM(
            'credit_welcome_bonus',
            'top_up',
            'bump_up_fee',
            'urgent_fee',
            'vip_fee',
            'verified_fee',
            'connect_fee'
        ) NOT NULL");
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM(
            'credit_welcome_bonus',
            'top_up',
            'bump_up_fee',
            'urgent_fee',
            'vip_fee',
            'verified_fee'
        ) NOT NULL");
    }
};
