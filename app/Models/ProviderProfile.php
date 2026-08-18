<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'city_id',
        'district_id',
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

    protected static function booted(): void
    {
        static::saving(function (ProviderProfile $profile) {
            if ($profile->city_id) {
                $profile->city = City::query()->find($profile->city_id)?->name ?? $profile->city;
            }
            if ($profile->district_id) {
                $profile->district = District::query()->find($profile->district_id)?->name ?? $profile->district;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cityRef(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function districtRef(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_provider_profile')
            ->withTimestamps();
    }

    /**
     * @param  list<int>  $ids
     */
    public function syncCategoryIds(array $ids): void
    {
        $ids = array_values(array_unique(array_slice(array_map('intval', $ids), 0, 3)));
        $this->categories()->sync($ids);
        if ($ids !== []) {
            $this->updateQuietly(['category_id' => $ids[0]]);
        }
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
