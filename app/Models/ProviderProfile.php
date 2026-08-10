<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'bio',
        'audio_intro_url',
        'is_verified',
        'is_vip',
        'latitude',
        'longitude',
        'city',
        'district',
        'rating_avg',
        'rating_count',
        'bumped_at',
        'vip_expires_at',
        'is_active',
    ];

    protected $attributes = [
        'is_verified' => false,
        'is_vip' => false,
        'is_active' => true,
        'rating_avg' => 0,
        'rating_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_vip' => 'boolean',
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'rating_avg' => 'float',
            'bumped_at' => 'datetime',
            'vip_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(RequestMatch::class);
    }
}
