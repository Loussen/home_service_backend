<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->timestamp('client_archived_at')->nullable()->after('last_message_at');
            $table->timestamp('provider_archived_at')->nullable()->after('client_archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['client_archived_at', 'provider_archived_at']);
        });
    }
};
