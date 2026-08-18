<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ServiceRequest extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'raw_audio_url',
        'transcribed_text',
        'parsed_criteria',
        'is_urgent',
        'latitude',
        'longitude',
        'address',
        'status',
        'bumped_at',
        'urgent_until',
    ];

    protected function casts(): array
    {
        return [
            'parsed_criteria' => 'array',
            'is_urgent' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'bumped_at' => 'datetime',
            'urgent_until' => 'datetime',
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

    public function matches(): HasMany
    {
        return $this->hasMany(RequestMatch::class);
    }

    public function getAudioPublicUrlAttribute(): ?string
    {
        $path = $this->raw_audio_url;
        if (! filled($path)) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url(Storage::disk('public')->url($path));
    }
}
