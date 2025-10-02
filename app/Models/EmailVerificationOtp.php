<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerificationOtp extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'otp_code',
        'expires_at',
        'attempts',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user that owns the OTP
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the OTP has been verified
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Check if the OTP has exceeded maximum attempts
     */
    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= 3;
    }

    /**
     * Increment the attempt count
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Mark the OTP as verified
     */
    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }

    /**
     * Scope to get active (non-expired, non-verified) OTPs
     */
    public function scopeActive($query)
    {
        return $query->whereNull('verified_at')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get OTPs for a specific user and email
     */
    public function scopeForUser($query, int $userId, string $email)
    {
        return $query->where('user_id', $userId)
                    ->where('email', $email);
    }
}
