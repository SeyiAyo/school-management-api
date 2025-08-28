<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class OnboardingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
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
        if ($user->role !== 'admin') {
            return $this->error('Only administrators can perform onboarding.', Response::HTTP_FORBIDDEN);
        }
        return null; // OK
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
     *         @OA\JsonContent(
     *             required={"name","type"},
     *             @OA\Property(property="name", type="string", example="Bright Academy"),
     *             @OA\Property(property="type", type="string", example="Primary"),
     *             @OA\Property(property="description", type="string", example="Our mission..."),
     *             @OA\Property(property="logo", type="string", description="Optional base64 or URL", nullable=true)
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
            // If you want to accept file uploads, switch to multipart/form-data and use image validation
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $user = Auth::user();

                $school = School::firstOrNew(['owner_user_id' => $user->id]);
                $school->fill([
                    'name' => $validated['name'],
                    'type' => $validated['type'],
                    'description' => $validated['description'] ?? $school->description,
                ]);
                if (! $school->exists) {
                    $school->status = 'pending';
                }
                $school->save();

                return $this->success($school, 'School identity saved');
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
     * Step 3: Complete onboarding
     *
     * @OA\Post(
     *     path="/api/onboarding/school-profile/complete",
     *     summary="Onboarding Step 3: Complete onboarding",
     *     tags={"Onboarding"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Onboarding complete"),
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

                $school->status = 'active';
                $school->save();

                $bootstrap = [
                    'school' => $school,
                    'modules' => ['Students','Teachers','Classes','Subjects','Attendance','Finance'],
                    'next_actions' => ['Upload Students','Add Teachers','Configure Classes','Set Up Finance'],
                ];

                return $this->success($bootstrap, 'Onboarding complete. Welcome to your dashboard.');
            });
        } catch (\Exception $e) {
            Log::error('Onboarding complete error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->internalServerError('Failed to complete onboarding');
        }
    }
}
