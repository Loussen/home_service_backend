<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('type', 20)->default('city');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['city_id', 'slug']);
        });

        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('longitude')->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('city_id');
        });
        Schema::dropIfExists('districts');
        Schema::dropIfExists('cities');
    }
};
