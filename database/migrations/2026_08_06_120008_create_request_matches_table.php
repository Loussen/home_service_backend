<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('match_score', 5, 2)->default(0);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->json('score_breakdown')->nullable();
            $table->boolean('notified')->default(false);
            $table->timestamps();

            $table->unique(['service_request_id', 'provider_profile_id'], 'request_matches_unique');
            $table->index('match_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_matches');
    }
};
