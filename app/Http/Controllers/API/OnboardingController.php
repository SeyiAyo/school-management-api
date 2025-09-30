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
            $schoolData['logo_url'] = $this->storageService->getFileUrl($school->logo_path);
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

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100', // school type: (e.g. primary, secondary, college etc.)
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048', // 2MB max
        ]);

        try {
            return DB::transaction(function () use ($validated, $request) {
                $user = Auth::user();

                $school = School::firstOrNew(['owner_user_id' => $user->id]);
                $school->fill([
                    'name' => $validated['name'],
                    'type' => $validated['type'],
                    'description' => $validated['description'] ?? $school->description,
                ]);

                // Handle logo upload to Supabase
                if ($request->hasFile('logo')) {
                    // Delete old logo if exists
                    if ($school->logo_path) {
                        $this->storageService->deleteFile($school->logo_path);
                    }

                    // Store new logo in Supabase
                    $logoPath = $this->storageService->storeFile($request->file('logo'), 'logos');
                    if ($logoPath) {
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
                    $schoolData['logo_url'] = $this->storageService->getFileUrl($school->logo_path);
                }

                return $this->success($schoolData, 'School identity saved');
            });
        } catch (\Exception $e) {
            Log::error('Onboarding step1 error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->internalServerError('Failed to save school identity');
        }
    }

    /**
     * Step 2: Contact and presence info
     *
     * @OA\Post(
     *     path="/api/onboarding/school-profile/step-2",
     *     summary="Onboarding Step 2: Contact info",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", nullable=true),
     *             @OA\Property(property="phone", type="string", nullable=true),
     *             @OA\Property(property="address", type="string", nullable=true),
     *             @OA\Property(property="website", type="string", format="uri", nullable=true),
     *             @OA\Property(property="accept_terms", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Contact info saved"),
     *     @OA\Response(response=403, description="Email not verified or not admin")
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
     * Step 3: Submit for verification
     *
     * @OA\Post(
     *     path="/api/onboarding/school-profile/complete",
     *     summary="Onboarding Step 3: Submit for verification",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"accept_verification_terms"},
     *             @OA\Property(property="accept_verification_terms", type="boolean", example=true,
     *                 description="User must accept that we will contact school for verification"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Submitted for verification"),
     *     @OA\Response(response=403, description="Email not verified or not admin")
     * )
     */
    public function complete(Request $request)
    {
        if ($resp = $this->ensureVerifiedAdmin()) {
            return $resp;
        }

        $validated = $request->validate([
            'accept_verification_terms' => 'required|boolean',
        ]);

        if (! $validated['accept_verification_terms']) {
            return $this->error('You must accept the verification terms to proceed.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            return DB::transaction(function () {
                $user = Auth::user();
                $school = School::where('owner_user_id', $user->id)->first();
                if (! $school) {
                    return $this->error('School profile not found. Complete previous steps first.', Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                $school->status = 'active'; // Submitted for verification
                $school->save();

                return $this->success([
                    'school' => $school,
                    'verification_status' => 'pending',
                    'message' => 'Your school information has been submitted for verification. We will contact your school to authenticate the details provided.',
                    'next_step' => 'verification_pending'
                ], 'Information submitted for verification.');
            });
        } catch (\Exception $e) {
            Log::error('Onboarding complete error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->internalServerError('Failed to submit for verification');
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
        $message = 'Your school verification is in progress.';
        
        if ($school->status === 'verified') {
            $status = 'verified';
            $message = 'Your school has been verified! You can now access all features.';
        } elseif ($school->status === 'rejected') {
            $status = 'rejected';
            $message = 'Your school verification was not successful. Please contact support.';
        }

        return $this->success([
            'status' => $status,
            'message' => $message,
            'submitted_at' => $school->updated_at,
            'disclaimer' => 'DISCLAIMER: By proceeding, you acknowledge that we will contact your school directly for verification and authentication purposes. This process may take 1-3 business days.',
            'school' => $school->toArray()
        ], 'Verification status retrieved');
    }

}
