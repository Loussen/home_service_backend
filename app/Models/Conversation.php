<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'client_id',
        'provider_id',
        'provider_profile_id',
        'service_request_id',
        'last_message_at',
        'client_archived_at',
        'provider_archived_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'client_archived_at' => 'datetime',
            'provider_archived_at' => 'datetime',
        ];
    }

    public function archiveColumnFor(User $user): string
    {
        if ((int) $this->client_id === (int) $user->id) {
            return 'client_archived_at';
        }

        abort_unless((int) $this->provider_id === (int) $user->id, 403, 'Not a participant');

        return 'provider_archived_at';
    }

    public function isArchivedFor(User $user): bool
    {
        $column = $this->archiveColumnFor($user);

        return $this->{$column} !== null;
    }

    /** Dual-role inbox: only threads for the user's active session side. */
    public function scopeForActiveRole(Builder $query, User $user): Builder
    {
        if ($user->isProvider()) {
            return $query->where('provider_id', $user->id);
        }

        return $query->where('client_id', $user->id);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, Offer::class);
    }

    public function hasSharedContact(): bool
    {
        return $this->offers()
            ->whereIn('status', [Offer::ACCEPTED, Offer::COMPLETED])
            ->exists();
    }
}
