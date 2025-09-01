<?php

namespace App\Services;

use App\Models\EmailVerificationOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate a new OTP for email verification
     *
     * @param User $user The user requesting verification
     * @param string $email Email address to verify
     * @return EmailVerificationOtp The generated OTP record
     */
    public function generateOtp(User $user, string $email): EmailVerificationOtp
    {
        // Invalidate any existing active OTPs for this user/email
        $this->invalidateExistingOtps($user->id, $email);

        // Generate 6-digit OTP code
        $otpCode = $this->generateOtpCode();

        // Create new OTP record
        $otp = EmailVerificationOtp::create([
            'user_id' => $user->id,
            'email' => $email,
            'otp_code' => $otpCode,
            'expires_at' => now()->addMinutes(5), // 5-minute expiry
            'attempts' => 0,
        ]);

        Log::info('OTP generated for email verification', [
            'user_id' => $user->id,
            'email' => $email,
            'otp_id' => $otp->id,
            'expires_at' => $otp->expires_at->toISOString()
        ]);

        return $otp;
    }

    /**
     * Validate an OTP code
     *
     * @param User $user The user attempting verification
     * @param string $email Email address being verified
     * @param string $otpCode The OTP code to validate
     * @return array Validation result with success status and message
     */
    public function validateOtp(User $user, string $email, string $otpCode): array
    {
        // Find the most recent active OTP for this user/email
        $otp = EmailVerificationOtp::forUser($user->id, $email)
            ->active()
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp) {
            Log::warning('OTP validation failed - no active OTP found', [
                'user_id' => $user->id,
                'email' => $email,
                'provided_code' => $otpCode
            ]);

            return [
                'success' => false,
                'message' => 'No valid OTP found. Please request a new verification code.',
                'code' => 'OTP_NOT_FOUND'
            ];
        }

        // Check if OTP has exceeded maximum attempts
        if ($otp->hasExceededAttempts()) {
            Log::warning('OTP validation failed - maximum attempts exceeded', [
                'user_id' => $user->id,
                'email' => $email,
                'otp_id' => $otp->id,
                'attempts' => $otp->attempts
            ]);

            return [
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Please request a new code.',
                'code' => 'MAX_ATTEMPTS_EXCEEDED'
            ];
        }

        // Check if OTP is expired
        if ($otp->isExpired()) {
            Log::warning('OTP validation failed - code expired', [
                'user_id' => $user->id,
                'email' => $email,
                'otp_id' => $otp->id,
                'expires_at' => $otp->expires_at->toISOString()
            ]);

            return [
                'success' => false,
                'message' => 'Verification code has expired. Please request a new code.',
                'code' => 'OTP_EXPIRED'
            ];
        }

        // Increment attempt count
        $otp->incrementAttempts();

        // Check if the code matches
        if ($otp->otp_code !== $otpCode) {
            Log::warning('OTP validation failed - incorrect code', [
                'user_id' => $user->id,
                'email' => $email,
                'otp_id' => $otp->id,
                'attempts' => $otp->attempts,
                'provided_code' => $otpCode
            ]);

            $remainingAttempts = 3 - $otp->attempts;
            $message = $remainingAttempts > 0 
                ? "Incorrect verification code. You have {$remainingAttempts} attempts remaining."
                : "Incorrect verification code. Maximum attempts exceeded.";

            return [
                'success' => false,
                'message' => $message,
                'code' => 'INVALID_OTP',
                'remaining_attempts' => $remainingAttempts
            ];
        }

        // OTP is valid - mark as verified
        $otp->markAsVerified();

        Log::info('OTP validation successful', [
            'user_id' => $user->id,
            'email' => $email,
            'otp_id' => $otp->id
        ]);

        return [
            'success' => true,
            'message' => 'Email verification successful.',
            'code' => 'OTP_VERIFIED'
        ];
    }

    /**
     * Generate a 6-digit OTP code
     *
     * @return string 6-digit numeric code
     */
    private function generateOtpCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Invalidate existing active OTPs for a user/email combination
     *
     * @param int $userId User ID
     * @param string $email Email address
     * @return void
     */
    private function invalidateExistingOtps(int $userId, string $email): void
    {
        $invalidatedCount = EmailVerificationOtp::forUser($userId, $email)
            ->active()
            ->update(['verified_at' => now()]);

        if ($invalidatedCount > 0) {
            Log::info('Invalidated existing OTPs', [
                'user_id' => $userId,
                'email' => $email,
                'count' => $invalidatedCount
            ]);
        }
    }

    /**
     * Clean up expired OTPs (can be called via scheduled job)
     *
     * @return int Number of cleaned up records
     */
    public function cleanupExpiredOtps(): int
    {
        $deletedCount = EmailVerificationOtp::where('expires_at', '<', now()->subHours(24))
            ->delete();

        if ($deletedCount > 0) {
            Log::info('Cleaned up expired OTPs', ['count' => $deletedCount]);
        }

        return $deletedCount;
    }

    /**
     * Get OTP statistics for monitoring
     *
     * @return array Statistics about OTP usage
     */
    public function getOtpStats(): array
    {
        $now = now();
        $last24Hours = $now->subHours(24);

        return [
            'active_otps' => EmailVerificationOtp::active()->count(),
            'expired_otps' => EmailVerificationOtp::where('expires_at', '<', $now)->whereNull('verified_at')->count(),
            'verified_otps_24h' => EmailVerificationOtp::whereNotNull('verified_at')
                ->where('verified_at', '>=', $last24Hours)->count(),
            'generated_otps_24h' => EmailVerificationOtp::where('created_at', '>=', $last24Hours)->count(),
        ];
    }
}
