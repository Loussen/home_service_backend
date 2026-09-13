<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // InnoDB may use conversations_pair_unique as the supporting index for
        // client_id / provider_id / provider_profile_id FKs. Give each FK its
        // own index first, then the composite unique can be dropped.
        $this->ensureIndex('conversations', 'conversations_client_id_fk_idx', ['client_id']);
        $this->ensureIndex('conversations', 'conversations_provider_id_fk_idx', ['provider_id']);
        $this->ensureIndex('conversations', 'conversations_provider_profile_id_fk_idx', ['provider_profile_id']);

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

    /**
     * @param  list<string>  $columns
     */
    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();

        if ($exists) {
            return;
        }

        $cols = implode(', ', array_map(fn (string $c) => "`{$c}`", $columns));
        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$cols})");
    }
};
