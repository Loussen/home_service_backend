<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IapPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'product_id',
        'purchase_token',
        'credit_amount',
        'pay_amount',
        'status',
        'transaction_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'credit_amount' => 'decimal:2',
            'pay_amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
