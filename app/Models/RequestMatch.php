<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestMatch extends Model
{
    protected $fillable = [
        'service_request_id',
        'provider_profile_id',
        'match_score',
        'distance_km',
        'score_breakdown',
        'notified',
        'missed_opportunity_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'float',
            'distance_km' => 'float',
            'score_breakdown' => 'array',
            'notified' => 'boolean',
            'missed_opportunity_notified_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
