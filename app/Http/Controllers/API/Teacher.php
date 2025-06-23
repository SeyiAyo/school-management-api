<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher as TeacherModel;

class Teacher extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/teachers",
     *     summary="Get all teachers",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Response(response=200, description="List of teachers retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        return TeacherModel::all();
    }

    /**
     * @OA\Post(
     *     path="/api/teachers",
     *     summary="Create a new teacher",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="subject_specialty", type="string"),
     *             @OA\Property(property="qualification", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="gender", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Teacher created successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:teachers',
            'phone' => 'nullable',
            'subject_specialty' => 'nullable',
            'qualification' => 'nullable',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable',
            'gender' => 'nullable',
        ]);

        $teacher = TeacherModel::create($request->all());
        return response()->json($teacher, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/teachers/{id}",
     *     summary="Get a specific teacher",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Teacher details retrieved successfully"),
     *     @OA\Response(response=404, description="Teacher not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(string $id)
    {
        $teacher = TeacherModel::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }
        return response()->json($teacher);
    }

    /**
     * @OA\Put(
     *     path="/api/teachers/{id}",
     *     summary="Update a teacher",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
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
     *             @OA\Property(property="subject_specialty", type="string"),
     *             @OA\Property(property="qualification", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="gender", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Teacher updated successfully"),
     *     @OA\Response(response=404, description="Teacher not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, string $id)
    {
        $teacher = TeacherModel::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }
        
        $request->validate([
            'name' => 'sometimes',
            'email' => 'sometimes|email|unique:teachers,email,' . $id,
            'phone' => 'nullable',
            'subject_specialty' => 'nullable',
            'qualification' => 'nullable',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable',
            'gender' => 'nullable',
        ]);
        
        $teacher->update($request->all());
        return response()->json($teacher);
    }

    /**
     * @OA\Delete(
     *     path="/api/teachers/{id}",
     *     summary="Delete a teacher",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Teacher deleted successfully"),
     *     @OA\Response(response=404, description="Teacher not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(string $id)
    {
        $teacher = TeacherModel::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }
        
        $teacher->delete();
        return response()->json(['message' => 'Teacher deleted successfully']);
    }
}
