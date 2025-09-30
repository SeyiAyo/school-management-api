<?php

namespace App\Http\Controllers\API;

use App\Enums\Role;
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
        if (!$user->isSuperAdmin()) {
            return $this->error('Unauthorized. Only super admins can verify schools.', Response::HTTP_FORBIDDEN);
        }

        $schools = School::with(['owner' => function($query) {
                $query->select('id', 'name', 'email', 'created_at');
            }])
            ->where('status', 'active') // Submitted for verification
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($school) {
                return [
                    'id' => $school->id,
                    'name' => $school->name,
                    'type' => $school->type,
                    'email' => $school->email,
                    'phone' => $school->phone,
                    'address' => $school->address,
                    'website' => $school->website,
                    'description' => $school->description,
                    'status' => $school->status,
                    'submitted_at' => $school->updated_at,
                    'days_pending' => $school->updated_at->diffInDays(now()),
                    'owner' => [
                        'id' => $school->owner->id,
                        'name' => $school->owner->name,
                        'email' => $school->owner->email,
                        'registered_at' => $school->owner->created_at,
                    ],
                    'logo_url' => $school->logo_path ? app(\App\Services\SupabaseStorageService::class)->getFileUrl($school->logo_path) : null,
                ];
            });

        $summary = [
            'total_pending' => $schools->count(),
            'oldest_submission' => $schools->max('days_pending'),
            'newest_submission' => $schools->min('days_pending'),
        ];

        return $this->success([
            'schools' => $schools,
            'summary' => $summary
        ], 'Pending schools retrieved successfully');
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
        if (!$user->isSuperAdmin()) {
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
                
                $freshSchool = $school->fresh(['owner']);
                
                return $this->success([
                    'school' => [
                        'id' => $freshSchool->id,
                        'name' => $freshSchool->name,
                        'type' => $freshSchool->type,
                        'email' => $freshSchool->email,
                        'phone' => $freshSchool->phone,
                        'address' => $freshSchool->address,
                        'website' => $freshSchool->website,
                        'status' => $freshSchool->status,
                        'verified_at' => $freshSchool->verified_at,
                        'verification_notes' => $freshSchool->verification_notes,
                        'owner' => [
                            'id' => $freshSchool->owner->id,
                            'name' => $freshSchool->owner->name,
                            'email' => $freshSchool->owner->email,
                        ],
                    ],
                    'verification' => [
                        'action' => $validated['action'],
                        'status' => $newStatus,
                        'verified_by' => $user->name,
                        'verified_at' => $freshSchool->verified_at,
                        'notes' => $validated['notes'],
                    ],
                    'next_steps' => $validated['action'] === 'approve' 
                        ? 'School owner will receive approval notification and can access full system features.'
                        : 'School owner will receive rejection notification with feedback for resubmission.',
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
        if (!$user->isSuperAdmin()) {
            return $this->error('Unauthorized. Only super admins can view verification stats.', Response::HTTP_FORBIDDEN);
        }

        // Basic counts
        $pendingCount = School::where('status', 'active')->count();
        $verifiedCount = School::where('status', 'verified')->count();
        $rejectedCount = School::where('status', 'rejected')->count();
        $totalCount = School::count();

        // Recent activity (last 30 days)
        $recentSubmissions = School::where('status', 'active')
            ->where('updated_at', '>=', now()->subDays(30))
            ->count();
        
        $recentVerifications = School::whereIn('status', ['verified', 'rejected'])
            ->where('verified_at', '>=', now()->subDays(30))
            ->count();

        // Average processing time
        $avgProcessingDays = School::whereIn('status', ['verified', 'rejected'])
            ->whereNotNull('verified_at')
            ->selectRaw('AVG(DATEDIFF(verified_at, updated_at)) as avg_days')
            ->value('avg_days');

        // Oldest pending submission
        $oldestPending = School::where('status', 'active')
            ->orderBy('updated_at', 'asc')
            ->first();

        $stats = [
            'overview' => [
                'pending' => $pendingCount,
                'verified' => $verifiedCount,
                'rejected' => $rejectedCount,
                'total' => $totalCount,
                'verification_rate' => $totalCount > 0 ? round(($verifiedCount / $totalCount) * 100, 1) : 0,
            ],
            'recent_activity' => [
                'submissions_last_30_days' => $recentSubmissions,
                'verifications_last_30_days' => $recentVerifications,
                'pending_backlog' => $pendingCount,
            ],
            'processing_metrics' => [
                'average_processing_days' => $avgProcessingDays ? round($avgProcessingDays, 1) : 0,
                'oldest_pending_days' => $oldestPending ? $oldestPending->updated_at->diffInDays(now()) : 0,
                'oldest_pending_school' => $oldestPending ? [
                    'id' => $oldestPending->id,
                    'name' => $oldestPending->name,
                    'submitted_at' => $oldestPending->updated_at,
                ] : null,
            ],
            'status_breakdown' => [
                'pending_percentage' => $totalCount > 0 ? round(($pendingCount / $totalCount) * 100, 1) : 0,
                'verified_percentage' => $totalCount > 0 ? round(($verifiedCount / $totalCount) * 100, 1) : 0,
                'rejected_percentage' => $totalCount > 0 ? round(($rejectedCount / $totalCount) * 100, 1) : 0,
            ],
        ];

        return $this->success($stats, 'Verification statistics retrieved successfully');
    }
}
