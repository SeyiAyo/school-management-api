<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student as StudentModel;

class Student extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/students",
     *     summary="Get all students",
     *     tags={"Students"},
     *     @OA\Response(response=200, description="List of students retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        return StudentModel::all();
    }

    /**
     * @OA\Post(
     *     path="/api/students",
     *     summary="Create a new student",
     *     tags={"Students"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="gender", type="string"),
     *             @OA\Property(property="parent_name", type="string"),
     *             @OA\Property(property="parent_phone", type="string"),
     *             @OA\Property(property="parent_email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Student created successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:students',
            'phone' => 'nullable',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable',
            'gender' => 'nullable',
            'parent_name' => 'nullable',
            'parent_phone' => 'nullable',
            'parent_email' => 'nullable|email',
        ]);

        $student = StudentModel::create($request->all());
        return response()->json($student, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/students/{id}",
     *     summary="Get a specific student",
     *     tags={"Students"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Student details retrieved successfully"),
     *     @OA\Response(response=404, description="Student not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(string $id)
    {
        $student = StudentModel::find($id);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }
        return response()->json($student);
    }

    /**
     * @OA\Put(
     *     path="/api/students/{id}",
     *     summary="Update a student",
     *     tags={"Students"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="gender", type="string"),
     *             @OA\Property(property="parent_name", type="string"),
     *             @OA\Property(property="parent_phone", type="string"),
     *             @OA\Property(property="parent_email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Student updated successfully"),
     *     @OA\Response(response=404, description="Student not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, string $id)
    {
        $student = StudentModel::find($id);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }
        
        $request->validate([
            'name' => 'sometimes',
            'email' => 'sometimes|email|unique:students,email,' . $id,
            'phone' => 'nullable',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable',
            'gender' => 'nullable',
            'parent_name' => 'nullable',
            'parent_phone' => 'nullable',
            'parent_email' => 'nullable|email',
        ]);
        
        $student->update($request->all());
        return response()->json($student);
    }

    /**
     * @OA\Delete(
     *     path="/api/students/{id}",
     *     summary="Delete a student",
     *     tags={"Students"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Student deleted successfully"),
     *     @OA\Response(response=404, description="Student not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(string $id)
    {
        $student = StudentModel::find($id);
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }
        
        $student->delete();
        return response()->json(['message' => 'Student deleted successfully']);
    }
}
