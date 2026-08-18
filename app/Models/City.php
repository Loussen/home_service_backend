<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'type',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function providerProfiles(): HasMany
    {
        return $this->hasMany(ProviderProfile::class);
    }
}
