<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\User;

class ReviewService
{
    public function create(User $user, int $offerId, int $rating, ?string $comment): Review
    {
        $offer = Offer::query()->with('conversation.providerProfile')->find($offerId);
        abort_if(! $offer, 404, 'Təklif tapılmadı');
        abort_unless($offer->status === Offer::COMPLETED, 422, 'Rəy yalnız tamamlanmış işdə yazılır');

        $conversation = $offer->conversation;
        abort_if(! $conversation?->provider_profile_id, 422, 'Profil tapılmadı');
        abort_if(
            $conversation->client_id !== $user->id && $conversation->provider_id !== $user->id,
            404,
            'Təklif tapılmadı'
        );

        $revieweeId = $conversation->client_id === $user->id
            ? $conversation->provider_id
            : $conversation->client_id;

        abort_if($revieweeId === $user->id, 422, 'Özünüzə rəy yaza bilməzsiniz');

        $exists = Review::query()
            ->where('offer_id', $offer->id)
            ->where('reviewer_id', $user->id)
            ->exists();
        abort_if($exists, 422, 'Bu işə artıq rəy yazmısınız');

        $review = Review::query()->create([
            'offer_id' => $offer->id,
            'service_request_id' => $conversation->service_request_id,
            'provider_profile_id' => $conversation->provider_profile_id,
            'client_id' => $conversation->client_id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $revieweeId,
            'rating' => $rating,
            'comment' => $comment,
        ]);

        $this->recalculateProfile($conversation->provider_profile_id);

        return $review->load('reviewer:id,name');
    }

    public function receivedBy(User $user)
    {
        return Review::query()
            ->with(['reviewer:id,name'])
            ->where('reviewee_id', $user->id)
            ->latest()
            ->limit(80)
            ->get();
    }

    private function recalculateProfile(?int $profileId): void
    {
        if (! $profileId) {
            return;
        }

        $profile = ProviderProfile::query()->find($profileId);
        if (! $profile) {
            return;
        }

        $stats = Review::query()
            ->where('provider_profile_id', $profile->id)
            ->where('reviewee_id', $profile->user_id)
            ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg_rating')
            ->first();

        $profile->update([
            'rating_count' => (int) ($stats->cnt ?? 0),
            'rating_avg' => round((float) ($stats->avg_rating ?? 0), 2),
        ]);
    }
}
