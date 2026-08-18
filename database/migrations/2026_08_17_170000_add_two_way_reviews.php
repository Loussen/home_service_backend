<?php

use App\Models\ProviderProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'offer_id')) {
                $table->foreignId('offer_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('reviews', 'reviewer_id')) {
                $table->foreignId('reviewer_id')->nullable()->after('client_id')->constrained('users')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('reviews', 'reviewee_id')) {
                $table->foreignId('reviewee_id')->nullable()->after('reviewer_id')->constrained('users')->cascadeOnDelete();
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM reviews'))->pluck('Key_name');
            if (! $indexes->contains('reviews_provider_profile_id_index')) {
                $table->index('provider_profile_id', 'reviews_provider_profile_id_index');
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM reviews'))->pluck('Key_name');
            if ($indexes->contains('reviews_unique')) {
                $table->dropUnique('reviews_unique');
            }
            if (! $indexes->contains('reviews_offer_reviewer_unique')) {
                $table->unique(['offer_id', 'reviewer_id'], 'reviews_offer_reviewer_unique');
            }
        });

        $profiles = ProviderProfile::query()->pluck('user_id', 'id');
        foreach (DB::table('reviews')->whereNull('reviewer_id')->get() as $row) {
            DB::table('reviews')->where('id', $row->id)->update([
                'reviewer_id' => $row->client_id,
                'reviewee_id' => $profiles[$row->provider_profile_id] ?? $row->client_id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_offer_reviewer_unique');
            $table->unique(
                ['provider_profile_id', 'client_id', 'service_request_id'],
                'reviews_unique'
            );
            $table->dropIndex('reviews_provider_profile_id_index');
            $table->dropConstrainedForeignId('offer_id');
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropConstrainedForeignId('reviewee_id');
        });
    }
};
