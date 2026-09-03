<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'phone',
        'name',
        'avatar_url',
        'active_role',
        'role_chosen_at',
        'provider_approval_status',
        'provider_approved_at',
        'provider_approved_by',
        'provider_rejection_note',
        'balance',
        'status',
        'welcome_bonus_granted',
        'phone_verified_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'welcome_bonus_granted' => 'boolean',
            'phone_verified_at' => 'datetime',
            'role_chosen_at' => 'datetime',
            'provider_approved_at' => 'datetime',
        ];
    }

    public function needsRole(): bool
    {
        return $this->role_chosen_at === null;
    }

    public function needsProviderApproval(): bool
    {
        return $this->isProvider()
            && $this->provider_approval_status !== 'approved';
    }

    public function isProviderApproved(): bool
    {
        return $this->isProvider()
            && $this->provider_approval_status === 'approved';
    }

    public function isProviderPending(): bool
    {
        return $this->isProvider()
            && $this->provider_approval_status === 'pending';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isProvider(): bool
    {
        return $this->active_role === 'provider';
    }

    public function isClient(): bool
    {
        return $this->active_role === 'client';
    }

    public function approvedByAdmin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class, 'provider_approved_by');
    }

    public function providerProfiles(): HasMany
    {
        return $this->hasMany(ProviderProfile::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(VerificationDocument::class);
    }

    public function clientConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'client_id');
    }

    public function providerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'provider_id');
    }
}
