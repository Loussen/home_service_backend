<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    public const PENDING = 'pending';

    public const ACCEPTED = 'accepted';

    public const DECLINED = 'declined';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    protected $fillable = [
        'conversation_id',
        'proposed_by',
        'scheduled_at',
        'duration_hours',
        'price_azn',
        'note',
        'status',
        'accepted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_hours' => 'float',
            'price_azn' => 'float',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function booking(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function sharesContact(): bool
    {
        return in_array($this->status, [self::ACCEPTED, self::COMPLETED], true);
    }
}
