<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass as SchoolClassModel;

class SchoolClass extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/classes",
     *     summary="Create a new class",
     *     tags={"Classes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","grade","teacher_id"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="grade", type="string"),
     *             @OA\Property(property="teacher_id", type="integer"),
     *             @OA\Property(property="students", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Class created successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'grade' => 'required',
            'teacher_id' => 'required|exists:teachers,id',
            'students' => 'nullable|array',
            'students.*' => 'exists:students,id',
        ]);

        $class = SchoolClassModel::create($request->only([
            'name',
            'grade',
            'teacher_id',
        ]));

        if ($request->has('students')) {
            $class->students()->attach($request->students);
        }

        return response()->json([
            'message' => 'Class created',
            'class' => $class,
        ], 201);
    }
}
