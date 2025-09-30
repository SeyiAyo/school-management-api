<?php

namespace App\Http\Controllers\API;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use App\Services\SupabaseStorageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class OnboardingController extends Controller
{
    protected $storageService;

    public function __construct(SupabaseStorageService $storageService)
    {
        $this->middleware('auth:sanctum');
        $this->storageService = $storageService;
    }

    /**
     * Ensure user is verified admin.
     */
    protected function ensureVerifiedAdmin()
    {
        $user = Auth::user();
        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED);
        }
        if (! $user->hasVerifiedEmail()) {
            return $this->error('Email not verified. Please verify to continue onboarding.', Response::HTTP_FORBIDDEN);
        }
        if (!$user->hasRole(Role::ADMIN)) {
            return $this->error('Only administrators can perform onboarding.', Response::HTTP_FORBIDDEN);
        }
        return null; // OK
    }

    /**
     * Get current onboarding status for the authenticated admin
     *
     * @OA\Get(
     *     path="/api/onboarding/status",
     *     summary="Get current onboarding status",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Onboarding status retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_step", type="integer", example=2),
     *                 @OA\Property(property="completed_steps", type="array", @OA\Items(type="integer")),
     *                 @OA\Property(property="is_complete", type="boolean", example=false),
     *                 @OA\Property(property="school", type="object", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function getStatus(Request $request)
    {
        if ($resp = $this->ensureVerifiedAdmin()) {
            return $resp;
        }

        $user = Auth::user();
        $school = School::where('owner_user_id', $user->id)->first();

        // Determine current step based on school data
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

            // Step 3: Complete
            if (in_array(2, $completedSteps) && $school->status === 'active') {
                $completedSteps[] = 3;
                $currentStep = 4;
            }

            // Step 4: School Verification
            if (in_array(3, $completedSteps) && $school->status === 'verified') {
                $completedSteps[] = 4;
                $currentStep = null; // Onboarding complete
                $isComplete = true;
            }
        }

        // Add logo URL if exists
        $schoolData = $school ? $school->toArray() : null;
        if ($school && $school->logo_path) {
            try {
                $useSupabase = env('SUPABASE_URL') && env('SUPABASE_ACCESS_KEY_ID');
                
                if ($useSupabase) {
                    $schoolData['logo_url'] = $this->storageService->getFileUrl($school->logo_path);
                } else {
                    // Generate local storage URL
                    $schoolData['logo_url'] = asset('storage/' . $school->logo_path);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to generate logo URL', [
                    'path' => $school->logo_path,
                    'error' => $e->getMessage()
                ]);
                $schoolData['logo_url'] = null;
            }
        }

        return $this->success([
            'current_step' => $currentStep,
            'completed_steps' => $completedSteps,
            'is_complete' => $isComplete,
            'school' => $schoolData,
        ], 'Onboarding status retrieved');
    }

    /**
     * Step 1: Core school identity
     *
     * @OA\Post(
     *     path="/api/onboarding/school-profile/step-1",
     *     summary="Onboarding Step 1: Create/Update school identity",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","type"},
     *                 @OA\Property(property="name", type="string", example="Bright Academy"),
     *                 @OA\Property(property="type", type="string", example="Primary"),
     *                 @OA\Property(property="description", type="string", example="Our mission..."),
     *                 @OA\Property(property="logo", type="string", format="binary", description="School logo image file")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="School saved"),
     *     @OA\Response(response=403, description="Email not verified or not admin")
     * )
     */
    public function step1(Request $request)
    {
        if ($resp = $this->ensureVerifiedAdmin()) {
            return $resp;
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:100', // school type: (e.g. primary, secondary, college etc.)
                'description' => 'nullable|string|max:1000',
                'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048', // 2MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                'Validation failed',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $e->errors()
            );
        }

        try {
            return DB::transaction(function () use ($validated, $request) {
                $user = Auth::user();

                $school = School::firstOrNew(['owner_user_id' => $user->id]);
                $school->fill([
                    'name' => $validated['name'],
                    'type' => $validated['type'],
                    'description' => $validated['description'] ?? $school->description,
                ]);

                // Handle logo upload
                if ($request->hasFile('logo')) {
                    // Check if Supabase is configured
                    $useSupabase = env('SUPABASE_URL') && env('SUPABASE_ACCESS_KEY_ID');
                    
                    if ($useSupabase) {
                        // Use Supabase storage (production)
                        Log::info('Using Supabase storage for logo upload');
                        
                        // Delete old logo if exists
                        if ($school->logo_path) {
                            $deleteResult = $this->storageService->deleteFile($school->logo_path);
                            if (!$deleteResult) {
                                Log::warning('Failed to delete old logo', ['path' => $school->logo_path]);
                            }
                        }

                        // Store new logo in Supabase
                        $logoPath = $this->storageService->storeFile($request->file('logo'), 'school-logos');
                        if (!$logoPath) {
                            Log::error('Failed to upload logo to Supabase');
                            throw new \Exception('Failed to upload logo. Please try again or contact administrator.');
                        }
                        
                        $school->logo_path = $logoPath;
                    } else {
                        // Fallback to local storage (development)
                        Log::info('Using local storage for logo upload (Supabase not configured)');
                        
                        // Delete old logo if exists
                        if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
                            Storage::disk('public')->delete($school->logo_path);
                            Log::info('Deleted old logo from local storage', ['path' => $school->logo_path]);
                        }

                        // Store new logo locally
                        $logoPath = $request->file('logo')->store('school-logos', 'public');
                        if (!$logoPath) {
                            Log::error('Failed to upload logo to local storage');
                            throw new \Exception('Failed to upload logo. Please try again.');
                        }
                        
                        Log::info('Logo uploaded to local storage', ['path' => $logoPath]);
                        $school->logo_path = $logoPath;
                    }
                }

                if (! $school->exists) {
                    $school->status = 'pending';
                }
                $school->save();

                // Add URL for logo if exists
                $schoolData = $school->toArray();
                if ($school->logo_path) {
                    try {
                        // Check if using Supabase or local storage
                        $useSupabase = env('SUPABASE_URL') && env('SUPABASE_ACCESS_KEY_ID');
                        
                        if ($useSupabase) {
                            $schoolData['logo_url'] = $this->storageService->getFileUrl($school->logo_path);
                        } else {
                            // Generate local storage URL
                            $schoolData['logo_url'] = asset('storage/' . $school->logo_path);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to generate logo URL', [
                            'path' => $school->logo_path,
                            'error' => $e->getMessage()
                        ]);
                        $schoolData['logo_url'] = null;
                    }
                }

                return $this->success($schoolData, 'School identity saved');
            });
        } catch (\Exception $e) {
            Log::error('Onboarding step1 error: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return specific error message if available
            $errorMessage = str_contains($e->getMessage(), 'storage') || 
                           str_contains($e->getMessage(), 'upload') ||
                           str_contains($e->getMessage(), 'configured')
                ? $e->getMessage()
                : 'Failed to save school identity. Please try again.';
            
            return $this->error($errorMessage, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Step 2: Contact info + Terms Acceptance
     *
     * @OA\Post(
     *     path="/api/onboarding/school-profile/step-2",
     *     summary="Onboarding Step 2: Contact info + Terms Acceptance",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "phone", "address", "accept_terms"},
     *             @OA\Property(property="email", type="string", format="email", example="school@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="address", type="string", example="123 School St, City, State"),
     *             @OA\Property(property="website", type="string", format="uri", nullable=true, example="https://school.edu"),
     *             @OA\Property(property="accept_terms", type="boolean", example=true, description="Must accept terms acknowledging school verification contact")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact info saved with disclaimer",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="School contact info saved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="school", type="object"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="disclaimer", type="string"),
     *                 @OA\Property(property="next_step", type="string", example="complete")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Email not verified or not admin"),
     *     @OA\Response(response=422, description="Terms not accepted")
     * )
     */
    public function step2(Request $request)
    {
        if ($resp = $this->ensureVerifiedAdmin()) {
            return $resp;
        }

        $validated = $request->validate([
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'accept_terms' => 'required|boolean',
        ]);

        if (! $validated['accept_terms']) {
            return $this->error('You must accept the terms and conditions to proceed.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            return DB::transaction(function () use ($validated) {
                $user = Auth::user();

                $school = School::firstOrCreate(['owner_user_id' => $user->id], [
                    'name' => '',
                    'type' => '',
                    'status' => 'pending',
                ]);

                $school->fill([
                    'email' => $validated['email'] ?? $school->email,
                    'phone' => $validated['phone'] ?? $school->phone,
                    'address' => $validated['address'] ?? $school->address,
                    'website' => $validated['website'] ?? $school->website,
                ]);
                $school->save();

                return $this->success($school, 'School contact info saved');
            });
        } catch (\Exception $e) {
            Log::error('Onboarding step2 error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->internalServerError('Failed to save school contact info');
        }
    }

    /**
     * Step 3: Final confirmation screen - Complete onboarding
     *
     * @OA\Post(
     *     path="/api/onboarding/school-profile/complete",
     *     summary="Onboarding Step 3: Complete onboarding (no action required)",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Onboarding complete - awaiting verification"),
     *     @OA\Response(response=403, description="Email not verified or not admin")
     * )
     */
    public function complete(Request $request)
    {
        if ($resp = $this->ensureVerifiedAdmin()) {
            return $resp;
        }

        try {
            return DB::transaction(function () {
                $user = Auth::user();
                $school = School::where('owner_user_id', $user->id)->first();
                if (! $school) {
                    return $this->error('School profile not found. Complete previous steps first.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                // Mark school as active (onboarding complete, awaiting verification)
                $school->status = 'active';
                $school->save();

                return $this->success([
                    'school' => $school,
                    'verification_status' => 'pending',
                    'message' => 'Onboarding complete! Your school information has been submitted for verification. Verification may take up to 3-5 business days. You will receive a confirmation email when your account is approved.',
                    'estimated_verification_time' => '3-5 business days'
                ], 'Onboarding complete. Awaiting verification.');
            });
        } catch (\Exception $e) {
            Log::error('Onboarding complete error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->internalServerError('Failed to complete onboarding');
        }
    }

    /**
     * Step 4: Verification pending status
     *
     * @OA\Get(
     *     path="/api/onboarding/verification-status",
     *     summary="Check verification status",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Verification status",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="submitted_at", type="string", format="datetime"),
     *                 @OA\Property(property="disclaimer", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function verificationStatus(Request $request)
    {
        if ($resp = $this->ensureVerifiedAdmin()) {
            return $resp;
        }

        $user = Auth::user();
        $school = School::where('owner_user_id', $user->id)->first();

        if (!$school) {
            return $this->error('School profile not found.', Response::HTTP_NOT_FOUND);
        }

        $status = 'pending';
        $message = 'Your school verification is in progress. Verification may take up to 3-5 business days. You will receive a confirmation email when your account is approved.';

        if ($school->status === 'verified') {
            $status = 'verified';
            $message = 'Your school has been verified! You can now access all features.';
        } elseif ($school->status === 'rejected') {
            $status = 'rejected';
            $message = 'Your school verification was not successful. Please contact support for more information.';
        }

        return $this->success([
            'status' => $status,
            'message' => $message,
            'submitted_at' => $school->updated_at,
            'estimated_verification_time' => '3-5 business days',
            'school' => $school->toArray()
        ], 'Verification status retrieved');
    }

}
