<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all schools pending verification
     *
     * @OA\Get(
     *     path="/api/admin/schools/pending-verification",
     *     summary="Get schools pending verification",
     *     tags={"Admin Verification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Schools pending verification",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getPendingSchools(Request $request)
    {
        // Only super admins can verify schools
        $user = Auth::user();
        if ($user->role !== 'super_admin') {
            return $this->error('Unauthorized. Only super admins can verify schools.', Response::HTTP_FORBIDDEN);
        }

        $schools = School::with('owner')
            ->where('status', 'active') // Submitted for verification
            ->orderBy('updated_at', 'desc')
            ->get();

        return $this->success($schools, 'Pending schools retrieved');
    }

    /**
     * Approve or reject school verification
     *
     * @OA\Post(
     *     path="/api/admin/schools/{school}/verify",
     *     summary="Approve or reject school verification",
     *     tags={"Admin Verification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="school",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action"},
     *             @OA\Property(property="action", type="string", enum={"approve", "reject"}),
     *             @OA\Property(property="notes", type="string", description="Optional verification notes")
     *         )
     *     ),
     *     @OA\Response(response=200, description="School verification updated"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function verifySchool(Request $request, School $school)
    {
        // Only super admins can verify schools
        $user = Auth::user();
        if ($user->role !== 'super_admin') {
            return $this->error('Unauthorized. Only super admins can verify schools.', Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($validated, $school, $user) {
                $newStatus = $validated['action'] === 'approve' ? 'verified' : 'rejected';
                
                $school->update([
                    'status' => $newStatus,
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'verification_notes' => $validated['notes'] ?? null,
                ]);

                $message = $validated['action'] === 'approve' 
                    ? 'School has been verified successfully.'
                    : 'School verification has been rejected.';

                // TODO: Send email notification to school admin
                
                return $this->success([
                    'school' => $school->fresh(),
                    'action' => $validated['action'],
                    'verified_by' => $user->name,
                ], $message);
            });
        } catch (\Exception $e) {
            Log::error('School verification error: ' . $e->getMessage(), [
                'school_id' => $school->id,
                'action' => $validated['action'],
                'trace' => $e->getTraceAsString()
            ]);
            return $this->internalServerError('Failed to update school verification');
        }
    }

    /**
     * Get verification statistics
     *
     * @OA\Get(
     *     path="/api/admin/verification/stats",
     *     summary="Get verification statistics",
     *     tags={"Admin Verification"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Verification statistics")
     * )
     */
    public function getVerificationStats(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'super_admin') {
            return $this->error('Unauthorized. Only super admins can view verification stats.', Response::HTTP_FORBIDDEN);
        }

        $stats = [
            'pending' => School::where('status', 'active')->count(),
            'verified' => School::where('status', 'verified')->count(),
            'rejected' => School::where('status', 'rejected')->count(),
            'total' => School::count(),
        ];

        return $this->success($stats, 'Verification statistics retrieved');
    }
}
