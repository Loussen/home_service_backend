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
            $table->string('provider_approval_status', 20)->nullable()->after('role_chosen_at');
            $table->timestamp('provider_approved_at')->nullable()->after('provider_approval_status');
            $table->foreignId('provider_approved_by')->nullable()->after('provider_approved_at')
                ->constrained('admins')->nullOnDelete();
            $table->string('provider_rejection_note', 500)->nullable()->after('provider_approved_by');
        });

        // Mövcud icraçılar artıq işləyir — təsdiqli sayılır
        DB::table('users')
            ->where('active_role', 'provider')
            ->whereNotNull('role_chosen_at')
            ->update([
                'provider_approval_status' => 'approved',
                'provider_approved_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_approved_by');
            $table->dropColumn([
                'provider_approval_status',
                'provider_approved_at',
                'provider_rejection_note',
            ]);
        });
    }
};
