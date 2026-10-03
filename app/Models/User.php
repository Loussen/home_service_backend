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
        'share_phone',
        'name',
        'avatar_url',
        'active_role',
        'has_client_role',
        'has_provider_role',
        'role_chosen_at',
        'provider_approval_status',
        'provider_approved_at',
        'provider_approved_by',
        'provider_rejection_note',
        'provider_resubmitted_at',
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
            'share_phone' => 'boolean',
            'has_client_role' => 'boolean',
            'has_provider_role' => 'boolean',
            'welcome_bonus_granted' => 'boolean',
            'phone_verified_at' => 'datetime',
            'role_chosen_at' => 'datetime',
            'provider_approved_at' => 'datetime',
            'provider_resubmitted_at' => 'datetime',
        ];
    }

    public function needsRole(): bool
    {
        return $this->role_chosen_at === null
            || (! $this->hasClientRole() && ! $this->hasProviderRole());
    }

    /** @return list<string> */
    public function enabledRoles(): array
    {
        $roles = [];
        if ($this->hasClientRole()) {
            $roles[] = 'client';
        }
        if ($this->hasProviderRole()) {
            $roles[] = 'provider';
        }

        return $roles;
    }

    public function hasClientRole(): bool
    {
        return (bool) $this->has_client_role;
    }

    public function hasProviderRole(): bool
    {
        return (bool) $this->has_provider_role;
    }

    public function canSwitchRole(): bool
    {
        return $this->hasClientRole() && $this->hasProviderRole();
    }

    public function hasRole(string $role): bool
    {
        return match ($role) {
            'client' => $this->hasClientRole(),
            'provider' => $this->hasProviderRole(),
            default => false,
        };
    }

    public function needsProviderApproval(): bool
    {
        return $this->isProvider()
            && $this->hasProviderRole()
            && $this->provider_approval_status !== 'approved';
    }

    /**
     * Discoverable / CONNECT-able as a provider — independent of active session role.
     */
    public function isProviderApproved(): bool
    {
        return $this->hasProviderRole()
            && $this->provider_approval_status === 'approved';
    }

    public function isProviderPending(): bool
    {
        return $this->isProvider()
            && $this->hasProviderRole()
            && $this->provider_approval_status === 'pending';
    }

    public function isProviderResubmitPending(): bool
    {
        return $this->isProviderPending()
            && $this->provider_resubmitted_at !== null;
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /** Active session is provider mode. */
    public function isProvider(): bool
    {
        return $this->active_role === 'provider';
    }

    /** Active session is client (family) mode. */
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

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
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
