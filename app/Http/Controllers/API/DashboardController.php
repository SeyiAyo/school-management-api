<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SchoolClass;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     summary="Get dashboard data",
     *     tags={"Dashboard"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Response(response=200, description="Dashboard data retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();
        $totalClasses = SchoolClass::count();

        $today = now()->toDateString();
        $attendanceToday = Attendance::where('date', $today)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count','status');

            return response()->json([
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'total_classes' => $totalClasses,
                'attendance_today' => [
                    'present' => $attendanceToday['present'] ?? 0,
                    'absent' => $attendanceToday['absent'] ?? 0,
                    'late' => $attendanceToday['late'] ?? 0,
                ]
            ]);
        }
}
