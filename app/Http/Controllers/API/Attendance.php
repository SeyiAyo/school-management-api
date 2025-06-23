<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance as AttendanceModel;

class Attendance extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/attendance",
     *     summary="Mark attendance for students",
     *     tags={"Attendance"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"class_id","date","records"},
     *             @OA\Property(property="class_id", type="integer"),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="records", type="array", @OA\Items(
     *                 @OA\Property(property="student_id", type="integer"),
     *                 @OA\Property(property="status", type="string", enum={"present", "absent", "late"})
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Attendance marked successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function mark(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late',
        ]);

        foreach ($request->records as $record) {
            AttendanceModel::updateOrCreate(
                ['student_id' => $record['student_id'], 'class_id' => $request->class_id, 'date' => $request->date],
                ['status' => $record['status']]
            );
        }

        return response()->json([
            'message' => 'Attendance marked successfully',
        ]);
    }
}
