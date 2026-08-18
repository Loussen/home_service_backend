<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Support\Collection;

class BookingService
{
    public function listFor(User $user): Collection
    {
        return Booking::query()
            ->with([
                'client:id,name',
                'provider:id,name',
                'providerProfile.category',
            ])
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhere('provider_id', $user->id);
            })
            ->orderByRaw("CASE WHEN status = 'scheduled' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at')
            ->limit(80)
            ->get();
    }

    public function createFromAcceptedOffer(Offer $offer): Booking
    {
        $conversation = $offer->conversation;
        abort_if(! $conversation?->provider_profile_id, 422, 'Profil tapılmadı');

        return Booking::query()->updateOrCreate(
            ['offer_id' => $offer->id],
            [
                'conversation_id' => $conversation->id,
                'service_request_id' => $conversation->service_request_id,
                'client_id' => $conversation->client_id,
                'provider_id' => $conversation->provider_id,
                'provider_profile_id' => $conversation->provider_profile_id,
                'scheduled_at' => $offer->scheduled_at,
                'duration_hours' => $offer->duration_hours,
                'price_azn' => $offer->price_azn,
                'note' => $offer->note,
                'status' => Booking::SCHEDULED,
                'completed_at' => null,
                'cancelled_at' => null,
            ]
        );
    }

    public function markCompleted(Offer $offer): void
    {
        Booking::query()
            ->where('offer_id', $offer->id)
            ->update([
                'status' => Booking::COMPLETED,
                'completed_at' => now(),
            ]);
    }

    public function cancel(User $user, int $bookingId): Booking
    {
        $booking = Booking::query()
            ->with('offer')
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhere('provider_id', $user->id);
            })
            ->find($bookingId);

        abort_if(! $booking, 404, 'Booking tapılmadı');
        abort_unless($booking->status === Booking::SCHEDULED, 422, 'Yalnız planlaşdırılmış işi ləğv etmək olar');

        $booking->update([
            'status' => Booking::CANCELLED,
            'cancelled_at' => now(),
        ]);

        if ($booking->offer && $booking->offer->status === Offer::ACCEPTED) {
            $booking->offer->update(['status' => Offer::CANCELLED]);
        }

        return $booking->fresh([
            'client:id,name',
            'provider:id,name',
            'providerProfile.category',
        ]);
    }
}
