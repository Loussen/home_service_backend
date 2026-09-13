<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_pair_unique');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unique(
                ['client_id', 'provider_profile_id', 'service_request_id'],
                'conversations_request_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_request_unique');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unique(
                ['client_id', 'provider_id', 'provider_profile_id'],
                'conversations_pair_unique'
            );
        });
    }
};
