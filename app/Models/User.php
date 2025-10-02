<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"name", "email", "password", "role"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(
 *         property="role", 
 *         type="string", 
 *         enum={"super_admin", "admin", "teacher", "student", "parent"},
 *         example="teacher"
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }
    

    
    /**
     * Get the teacher profile associated with the user.
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }
    
    /**
     * Get the student profile associated with the user.
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }
    
    /**
     * Get the parent profile associated with the user.
     */
    public function parent()
    {
        return $this->hasOne(ParentModel::class);
    }

    /**
     * Check if user has admin role (admin or super_admin)
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Check if user has super admin role
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->role->isSuperAdmin();
    }

    /**
     * Check if user has specific role
     *
     * @param Role $role
     * @return bool
     */
    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Send the email verification notification using ResendEmailService.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        try {
            // Generate OTP for email verification
            $otpService = app(\App\Services\OtpService::class);
            $otp = $otpService->generateOtp($this, $this->getEmailForVerification());
            
            // Use ResendEmailService to send the OTP email
            $resendService = app(\App\Services\ResendEmailService::class);
            
            $result = $resendService->sendEmailVerification(
                $this->getEmailForVerification(),
                $otp->otp_code,
                $this->name
            );

            if ($result === false) {
                throw new \Exception('Failed to send OTP email verification');
            }

            \Illuminate\Support\Facades\Log::info('Email verification sent successfully', [
                'user_id' => $this->id,
                'email' => $this->getEmailForVerification()
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send email verification', [
                'user_id' => $this->id,
                'email' => $this->getEmailForVerification(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
