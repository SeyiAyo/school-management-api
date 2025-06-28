<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher as TeacherModel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Teacher extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum', ['except' => ['index', 'show']]);
        $this->authorizeResource(TeacherModel::class, 'teacher');
    }
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
            'email' => 'required|email|unique:users',
            'phone' => 'nullable',
            'subject_specialty' => 'nullable',
            'qualification' => 'nullable',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable',
            'gender' => 'nullable',
        ]);
        
        // Generate a random password
        $password = Str::random(8);
        
        DB::beginTransaction();
        
        try {
            // Create user with teacher role
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role' => 'teacher',
            ]);
            
            // Create teacher profile
            $teacher = TeacherModel::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
                'subject_specialty' => $request->subject_specialty,
                'qualification' => $request->qualification,
                'date_of_birth' => $request->date_of_birth,
                'address' => $request->address,
                'gender' => $request->gender,
            ]);
            
            DB::commit();
            
            // Return the teacher data along with the plain text password
            return response()->json([
                'user' => $user,
                'teacher' => $teacher,
                'generated_password' => $password
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create teacher', 'error' => $e->getMessage()], 500);
        }
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
