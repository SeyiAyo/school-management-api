<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ParentModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register school administrator (email verification required)",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","email","password","password_confirmation","position"},
     *             @OA\Property(property="first_name", type="string", example="John", maxLength=100),
     *             @OA\Property(property="last_name", type="string", example="Doe", maxLength=100),
     *             @OA\Property(property="email", type="string", format="email", example="admin@school.com", maxLength=255),
     *             @OA\Property(property="password", type="string", format="password", example="password123", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123", description="Must match the password field"),
     *             @OA\Property(property="phone", type="string", example="+2348012345678", maxLength=20),
     *             @OA\Property(property="position", type="string", example="Principal", maxLength=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Administrator registered; verification email sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful. Please verify your email."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="role", type="string", example="admin"),
     *                 @OA\Property(property="requires_email_verification", type="boolean", example=true),
     *                 @OA\Property(property="verification_token", type="string", example="1|abcdef123456", description="Temporary token for email verification")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email has already been taken.")
     *                 ),
     *                 @OA\Property(property="password", type="array",
     *                     @OA\Items(type="string", example="The password confirmation does not match.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to register user. Please try again later.")
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
                'position' => 'required|string|max:100',  //position at school
            ]);

            $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);

            // Create user with admin role
            $user = User::create([
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
            ]);

            // Send email verification notification
            $user->sendEmailVerificationNotification();

            // Issue temporary verification token for OTP verification
            $tempToken = $user->createToken('email-verification', ['email-verification'])->plainTextToken;

            return $this->success([
                    'user' => $user,
                    'role' => 'admin',
                    'requires_email_verification' => true,
                    'verification_token' => $tempToken,
            ], 'Registration successful. Please verify your email.', Response::HTTP_CREATED);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                'Validation failed',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                'Failed to register user. Please try again later.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login for all user types",
     *     description="Authenticates a user and returns an access token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials",
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="profile", type="object", nullable=true,
     *                     description="User's profile data based on role (teacher/student/parent)"
     *                 ),
     *                 @OA\Property(property="role", type="string", example="teacher"),
     *                 @OA\Property(property="access_token", type="string", example="1|abcdef123456"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="onboarding_status", type="object", nullable=true,
     *                     description="Onboarding status for admin users only",
     *                     @OA\Property(property="current_step", type="integer", nullable=true, example=2),
     *                     @OA\Property(property="completed_steps", type="array", @OA\Items(type="integer")),
     *                     @OA\Property(property="is_complete", type="boolean", example=false),
     *                     @OA\Property(property="requires_onboarding", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Invalid credentials"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Email not verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Please verify your email address before logging in. Check your inbox for the verification link."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="requires_email_verification", type="boolean", example=true),
     *                 @OA\Property(property="email", type="string", example="admin@school.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email must be a valid email address.")
     *                 ),
     *                 @OA\Property(property="password", type="array",
     *                     @OA\Items(type="string", example="The password field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to login. Please try again later.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (! Auth::attempt($credentials)) {
                return $this->error('Invalid credentials', Response::HTTP_UNAUTHORIZED);
            }

            $user = Auth::user();

            // Check if email is verified for admin users
            if ($user->role === 'admin' && !$user->hasVerifiedEmail()) {
                Auth::logout();
                return $this->error('Please verify your email address before logging in. Check your inbox for the verification link.', Response::HTTP_FORBIDDEN, [
                    'requires_email_verification' => true,
                    'email' => $user->email
                ]);
            }

            // Create token with role-specific ability
            $token = $user->createToken('auth_token', ['role:' . $user->role])->plainTextToken;

            // Get the profile data based on role
            $profile = null;
            switch ($user->role) {
                case 'student':
                    $profile = Student::where('user_id', $user->id)->first();
                    break;
                case 'teacher':
                    $profile = Teacher::where('user_id', $user->id)->first();
                    break;
                case 'parent':
                    $profile = ParentModel::where('user_id', $user->id)->first();
                    break;
            }

            // Check onboarding status for admins
            $onboardingStatus = null;
            if ($user->role === 'admin') {
                $school = \App\Models\School::where('owner_user_id', $user->id)->first();
                
                $currentStep = 1;
                $completedSteps = [];
                $isComplete = false;

                if ($school) {
                    // Step 1: Basic school info
                    if ($school->name && $school->type) {
                        $completedSteps[] = 1;
                        $currentStep = 2;
                    }

                    // Step 2: Contact info and terms
                    if (in_array(1, $completedSteps) && ($school->email || $school->phone || $school->address)) {
                        $completedSteps[] = 2;
                        $currentStep = 3;
                    }

                    // Step 3: Submit for verification
                    if (in_array(2, $completedSteps) && $school->status === 'active') {
                        $completedSteps[] = 3;
                        $currentStep = 4;
                    }

                    // Step 4: Verification complete
                    if (in_array(3, $completedSteps) && $school->status === 'verified') {
                        $completedSteps[] = 4;
                        $currentStep = null;
                        $isComplete = true;
                    }
                }

                $onboardingStatus = [
                    'current_step' => $currentStep,
                    'completed_steps' => $completedSteps,
                    'is_complete' => $isComplete,
                    'requires_onboarding' => !$isComplete,
                ];
            }

            return $this->success([
                    'user' => $user,
                    'profile' => $profile,
                    'role' => $user->role,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'onboarding_status' => $onboardingStatus,
            ], 'Login successful');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                'Validation failed',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                'Failed to login. Please try again later.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user (revoke token)",
     *     description="Revokes the current access token, effectively logging out the user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully logged out"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to logout. Please try again.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();

            return $this->success(
                null,
                'Successfully logged out',
                Response::HTTP_OK
            );

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                'Failed to logout. Please try again.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
