<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SchoolClass;

class DashboardController extends AuthController
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     summary="Get dashboard data",
     *     description="Retrieves key metrics and statistics for the admin dashboard",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard data retrieved successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="totals", type="object",
     *                     @OA\Property(property="students", type="integer", example=150),
     *                     @OA\Property(property="teachers", type="integer", example=20),
     *                     @OA\Property(property="classes", type="integer", example=15)
     *                 ),
     *                 @OA\Property(property="attendance_today", type="object",
     *                     @OA\Property(property="present", type="integer", example=120),
     *                     @OA\Property(property="absent", type="integer", example=20),
     *                     @OA\Property(property="late", type="integer", example=10),
     *                     @OA\Property(property="total", type="integer", example=150)
     *                 ),
     *                 @OA\Property(property="recent_activities", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="type", type="string", example="attendance"),
     *                         @OA\Property(property="title", type="string", example="New attendance recorded"),
     *                         @OA\Property(property="time", type="string", example="2 hours ago")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=401)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to load dashboard data"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=500)
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            // Get basic counts
            $totalStudents = Student::count();
            $totalTeachers = Teacher::count();
            $totalClasses = SchoolClass::count();

            // Get today's attendance
            $today = now()->toDateString();
            $attendanceToday = Attendance::where('date', $today)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Calculate total attendance for today
            $totalAttendanceToday = array_sum($attendanceToday);

            // Prepare response data
            $data = [
                'totals' => [
                    'students' => $totalStudents,
                    'teachers' => $totalTeachers,
                    'classes' => $totalClasses,
                ],
                'attendance_today' => [
                    'present' => $attendanceToday['present'] ?? 0,
                    'absent' => $attendanceToday['absent'] ?? 0,
                    'late' => $attendanceToday['late'] ?? 0,
                    'total' => $totalAttendanceToday
                ],
                'recent_activities' => $this->getRecentActivities()
            ];

            return $this->success($data, 'Dashboard data retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->internalServerError(
                'Failed to load dashboard data. Please try again later.',
                ['error' => config('app.debug') ? $e->getMessage() : null]
            );
        }
    }

    /**
     * Get recent activities for the dashboard
     * 
     * @return array
     */
    /**
     * Format an internal server error JSON response.
     *
     * @param string $message Error message
     * @param array $errors Optional array of additional error details
     * @return \Illuminate\Http\JsonResponse
     */
    protected function internalServerError(string $message = 'Internal server error', array $errors = [])
    {
        return $this->error(
            $message,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $errors
        );
    }

    /**
     * Get recent activities for the dashboard
     * 
     * @return array
     */
    protected function getRecentActivities(): array
    {
        // This is a placeholder. In a real application, you would fetch actual activities
        // from your activity log or relevant models
        return [
            [
                'id' => 1,
                'type' => 'attendance',
                'title' => 'Morning attendance marked',
                'time' => '2 hours ago'
            ],
            [
                'id' => 2,
                'type' => 'student',
                'title' => 'New student registered',
                'time' => '5 hours ago'
            ]
        ];
    }
}
