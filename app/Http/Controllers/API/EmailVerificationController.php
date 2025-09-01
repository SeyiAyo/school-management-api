<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\ResendEmailService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationController extends Controller
{
    protected $otpService;
    protected $emailService;

    public function __construct(OtpService $otpService, ResendEmailService $emailService)
    {
        $this->otpService = $otpService;
        $this->emailService = $emailService;
    }

    /**
     * Verify email using OTP code
     *
     * @OA\Post(
     *     path="/api/email/verify-otp",
     *     summary="Verify email with OTP code",
     *     tags={"Email Verification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"otp_code"},
     *             @OA\Property(property="otp_code", type="string", example="123456", description="6-digit verification code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email verified successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid verification code.")
     *         )
     *     )
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6'
        ]);

        $user = $request->user();

        // Verify user has email-verification token ability
        if (!$user->tokenCan('email-verification')) {
            return $this->error('Invalid verification token. Please use the token provided during registration.', Response::HTTP_FORBIDDEN);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Email already verified.');
        }

        $result = $this->otpService->validateOtp(
            $user,
            $user->email,
            $request->otp_code
        );

        if (!$result['success']) {
            return $this->error($result['message'], Response::HTTP_BAD_REQUEST, [
                'code' => $result['code'],
                'remaining_attempts' => $result['remaining_attempts'] ?? null
            ]);
        }

        // Mark email as verified
        $user->markEmailAsVerified();
        event(new Verified($user));

        // Revoke the temporary verification token
        $user->currentAccessToken()->delete();

        return $this->success(null, 'Email verified successfully. Please login with your credentials.');
    }

    /**
     * Resend OTP verification code to the authenticated user
     *
     * @OA\Post(
     *     path="/api/email/verification-notification",
     *     summary="Resend OTP verification code",
     *     tags={"Email Verification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Verification code sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verification code sent.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Too many verification attempts.")
     *         )
     *     )
     * )
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        // Verify user has email-verification token ability
        if (!$user->tokenCan('email-verification')) {
            return $this->error('Invalid verification token. Please use the token provided during registration.', Response::HTTP_FORBIDDEN);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Email already verified.');
        }

        // Generate new OTP
        $otp = $this->otpService->generateOtp($user, $user->email);

        // Send OTP email
        $result = $this->emailService->sendEmailVerification(
            $user->email,
            $otp->otp_code,
            $user->name
        );

        if ($result === false) {
            return $this->error('Failed to send verification code. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->success(null, 'Verification code sent to your email.');
    }
}
