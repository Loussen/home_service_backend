<?php

use App\Models\ProviderProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_provider_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['category_id', 'provider_profile_id'], 'cpp_category_profile_unique');
        });

        ProviderProfile::query()->each(function (ProviderProfile $profile) {
            if ($profile->category_id) {
                $profile->categories()->syncWithoutDetaching([$profile->category_id]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_provider_profile');
    }
};
