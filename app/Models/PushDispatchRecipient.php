<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushDispatchRecipient extends Model
{
    protected $fillable = [
        'push_dispatch_id',
        'user_id',
        'status',
        'error',
    ];

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(PushDispatch::class, 'push_dispatch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'delivered' => 'Çatdı',
            'skipped_no_token' => 'Tokensuz',
            'failed' => 'Uğursuz',
            default => $this->status ?: '—',
        };
    }
}
