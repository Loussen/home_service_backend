<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('urgent_until')->index();
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('processing','active','matched','completed','cancelled','expired') NOT NULL DEFAULT 'processing'");
        }

        // Backfill open requests: default 1 hour from creation.
        DB::table('service_requests')
            ->whereNull('expires_at')
            ->whereNotIn('status', ['completed', 'cancelled', 'expired'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $created = $row->created_at ?? now();
                    DB::table('service_requests')
                        ->where('id', $row->id)
                        ->update([
                            'expires_at' => \Carbon\Carbon::parse($created)->addHour(),
                        ]);
                }
            });

        DB::table('service_requests')
            ->whereNotIn('status', ['expired', 'completed', 'cancelled'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::table('service_requests')->where('status', 'expired')->update(['status' => 'cancelled']);
            DB::statement("ALTER TABLE service_requests MODIFY COLUMN status ENUM('processing','active','matched','completed','cancelled') NOT NULL DEFAULT 'processing'");
        }

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
