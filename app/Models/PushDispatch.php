<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PushDispatch extends Model
{
    protected $fillable = [
        'source',
        'type',
        'title',
        'body',
        'payload',
        'audience',
        'admin_id',
        'service_request_id',
        'conversation_id',
        'targeted_count',
        'delivered_count',
        'skipped_count',
        'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'targeted_count' => 'integer',
            'delivered_count' => 'integer',
            'skipped_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(PushDispatchRecipient::class);
    }

    public function refreshCounts(): void
    {
        $this->targeted_count = $this->recipients()->count();
        $this->delivered_count = $this->recipients()->where('status', 'delivered')->count();
        $this->skipped_count = $this->recipients()->where('status', 'skipped_no_token')->count();
        $this->failed_count = $this->recipients()->where('status', 'failed')->count();
        $this->save();
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'admin' => 'Admin',
            'request' => 'Sorğu',
            'chat' => 'Chat',
            'test' => 'Test',
            default => 'Sistem',
        };
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'admin' => 'Admin elanı',
            'new_job' => 'Yeni sorğu',
            'urgent_job' => 'Təcili sorğu',
            'chat_connect' => 'CONNECT',
            'chat_message' => 'Mesaj',
            default => $this->type ?: '—',
        };
    }

    public function audienceLabel(): ?string
    {
        return match ($this->audience) {
            'all' => 'Hamı',
            'clients' => 'Ailə',
            'providers' => 'Xidmətçilər',
            'selected' => 'Seçilmişlər',
            default => $this->audience,
        };
    }
}
