<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    public const SCHEDULED = 'scheduled';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'offer_id',
        'conversation_id',
        'service_request_id',
        'client_id',
        'provider_id',
        'provider_profile_id',
        'scheduled_at',
        'duration_hours',
        'price_azn',
        'note',
        'status',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_hours' => 'float',
            'price_azn' => 'float',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
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
}
