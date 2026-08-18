<?php

use App\Models\Offer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->decimal('duration_hours', 4, 1)->nullable();
            $table->decimal('price_azn', 8, 2);
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status', 'scheduled_at']);
            $table->index(['provider_id', 'status', 'scheduled_at']);
        });

        foreach (Offer::query()->with('conversation')->whereIn('status', ['accepted', 'completed'])->get() as $offer) {
            $conversation = $offer->conversation;
            if (! $conversation?->provider_profile_id) {
                continue;
            }

            DB::table('bookings')->insert([
                'offer_id' => $offer->id,
                'conversation_id' => $offer->conversation_id,
                'service_request_id' => $conversation->service_request_id,
                'client_id' => $conversation->client_id,
                'provider_id' => $conversation->provider_id,
                'provider_profile_id' => $conversation->provider_profile_id,
                'scheduled_at' => $offer->scheduled_at,
                'duration_hours' => $offer->duration_hours,
                'price_azn' => $offer->price_azn,
                'note' => $offer->note,
                'status' => $offer->status === 'completed' ? 'completed' : 'scheduled',
                'completed_at' => $offer->completed_at,
                'cancelled_at' => null,
                'created_at' => $offer->accepted_at ?? $offer->created_at,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
