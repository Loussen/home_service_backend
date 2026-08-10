<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('raw_audio_url')->nullable();
            $table->text('transcribed_text')->nullable();
            $table->json('parsed_criteria')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address')->nullable();
            $table->enum('status', ['processing', 'active', 'matched', 'completed', 'cancelled'])
                ->default('processing');
            $table->timestamp('bumped_at')->nullable();
            $table->timestamp('urgent_until')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
            $table->index(['status', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
