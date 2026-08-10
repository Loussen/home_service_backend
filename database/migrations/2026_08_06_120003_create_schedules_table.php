<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->comment('1: Mon … 7: Sun');
            $table->enum('time_slot', ['morning', 'afternoon', 'evening', 'night']);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(
                ['provider_profile_id', 'day_of_week', 'time_slot'],
                'schedules_profile_day_slot_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
