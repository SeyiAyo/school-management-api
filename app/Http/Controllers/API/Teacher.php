<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Teacher as TeacherModel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Teacher extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum', ['except' => ['index', 'getName']]);
        $this->authorizeResource(TeacherModel::class, 'teacher');
    }

    /**
     * @OA\Get(
     *     path="/api/teachers",
     *     summary="Get all teachers",
     *     description="Retrieves a list of all teachers with their user details",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="List of teachers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Teachers retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Teacher")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve teachers")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $teachers = TeacherModel::with('user')->get();
            return $this->success($teachers, 'Teachers retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve teachers: ' . $e->getMessage());
            return $this->error('Failed to retrieve teachers', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/teachers",
     *     summary="Create a new teacher",
     *     description="Creates a new teacher with the provided information and generates a random password",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Teacher data",
     *         @OA\JsonContent(
     *             required={"name", "email", "qualification"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="teacher@example.com"),
     *             @OA\Property(property="phone", type="string", maxLength=20, example="+1234567890"),
     *             @OA\Property(property="subject_specialty", type="string", maxLength=255, example="Mathematics"),
     *             @OA\Property(
     *                 property="qualification",
     *                 type="string",
     *                 enum={"High School Diploma", "Associate Degree", "Bachelor's Degree", "Master's Degree", "Doctorate (Ph.D.)", "Professional Certification", "Trade School Diploma", "Postgraduate Certificate/Diploma", "Other"},
     *                 example="Master's Degree",
     *                 description="Teacher's highest educational qualification"
     *             ),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="address", type="string", maxLength=500, example="123 Main St, City, Country"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, example="male")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Teacher created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Teacher created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="teacher@example.com")
     *                 ),
     *                 @OA\Property(property="teacher", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="subject_specialty", type="string", example="Mathematics"),
     *                     @OA\Property(property="qualification", type="string", example="Master's Degree")
     *                 ),
     *                 @OA\Property(property="generated_password", type="string", example="aBcDeFgH")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email has already been taken.")
     *                 ),
     *                 @OA\Property(property="qualification", type="array",
     *                     @OA\Items(type="string", example="The selected qualification is invalid. Valid options are: High School Diploma, Associate Degree, ...")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create teacher")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'subject_specialty' => 'nullable|string|max:255',
                'qualification' => 'required|' . TeacherModel::getQualificationValidationRule(),
                'date_of_birth' => 'nullable|date|before:today',
                'address' => 'nullable|string|max:500',
                'gender' => 'nullable|in:male,female,other',
            ], [
                'qualification.in' => 'The selected qualification is invalid. Valid options are: ' .
                                     implode(', ', array_values(TeacherModel::getQualifications()))
            ]);

            // Generate a random password
            $password = Str::random(8);

            DB::beginTransaction();

            // Create user with teacher role
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
                'role' => 'teacher',
            ]);

            // Create teacher profile
            $teacher = TeacherModel::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'] ?? null,
                'subject_specialty' => $validated['subject_specialty'] ?? null,
                'qualification' => $validated['qualification'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'gender' => $validated['gender'] ?? null,
            ]);

            DB::commit();

            return $this->success(
                [
                    'user' => $user->only(['id', 'name', 'email']),
                    'teacher' => $teacher->only(['id', 'subject_specialty', 'qualification']),
                    'generated_password' => $password
                ],
                'Teacher created successfully',
                Response::HTTP_CREATED
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                'Validation failed',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $e->errors()
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create teacher: ' . $e->getMessage());
            return $this->error(
                'Failed to create teacher',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/teachers/{id}",
     *     summary="Get a specific teacher",
     *     description="Retrieves detailed information about a specific teacher including their user details",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the teacher to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Teacher retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/TeacherWithUser")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teacher not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Teacher not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve teacher")
     *         )
     *     )
     * )
     */
    public function show(TeacherModel $teacher)
    {
        try {
            // The teacher is already loaded via route model binding
            // Just eager load the user relationship
            $teacher->load('user');

            return $this->success(
                $teacher,
                'Teacher retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Failed to retrieve teacher: ' . $e->getMessage());
            return $this->error(
                'Failed to retrieve teacher',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/teachers/{id}",
     *     summary="Update a teacher",
     *     description="Updates the specified teacher with the provided information",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the teacher to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Teacher data to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="John Doe", description="Teacher's full name"),
     *             @OA\Property(property="email", type="string", format="email", example="updated.teacher@example.com", description="Teacher's email address (must be unique)"),
     *             @OA\Property(property="phone", type="string", maxLength=20, example="+1234567890", description="Teacher's contact number"),
     *             @OA\Property(property="subject_specialty", type="string", maxLength=255, example="Advanced Mathematics", description="Teacher's subject specialty"),
     *             @OA\Property(
     *                 property="qualification",
     *                 type="string",
     *                 enum={"High School Diploma", "Associate Degree", "Bachelor's Degree", "Master's Degree", "Doctorate (Ph.D.)", "Professional Certification", "Trade School Diploma", "Postgraduate Certificate/Diploma", "Other"},
     *                 example="Doctorate (Ph.D.)",
     *                 description="Teacher's highest educational qualification"
     *             ),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1985-05-15", description="Teacher's date of birth"),
     *             @OA\Property(property="address", type="string", maxLength=500, example="456 University Ave, City, Country", description="Teacher's full address"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, example="male", description="Teacher's gender")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Teacher updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/TeacherWithUser")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No data provided for update")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teacher not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Teacher not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email has already been taken.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update teacher")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        try {
            $teacher = TeacherModel::with('user')->find($id);

            if (!$teacher) {
                return $this->error('Teacher not found', Response::HTTP_NOT_FOUND);
            }

            // Check if any data is provided for update
            if ($request->all() === []) {
                return $this->error('No data provided for update', Response::HTTP_BAD_REQUEST);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $teacher->user_id,
                'phone' => 'nullable|string|max:20',
                'subject_specialty' => 'nullable|string|max:255',
                'qualification' => 'sometimes|' . TeacherModel::getQualificationValidationRule(),
                'date_of_birth' => 'nullable|date|before:today',
                'address' => 'nullable|string|max:500',
                'gender' => 'nullable|in:male,female,other',
            ], [
                'qualification.in' => 'The selected qualification is invalid. Valid options are: ' .
                                     implode(', ', array_values(TeacherModel::getQualifications()))
            ]);

            DB::beginTransaction();

            // Update user if name or email is provided
            if (isset($validated['name']) || isset($validated['email'])) {
                $user = $teacher->user;
                if (isset($validated['name'])) $user->name = $validated['name'];
                if (isset($validated['email'])) $user->email = $validated['email'];
                $user->save();
            }

            // Prepare teacher data (exclude user-related fields)
            $teacherData = collect($validated)
                ->except(['name', 'email'])
                ->filter() // Remove null values
                ->toArray();

            // Update teacher if there's any data to update
            if (!empty($teacherData)) {
                $teacher->update($teacherData);
            }

            DB::commit();

            return $this->success(
                $teacher->load('user'),
                'Teacher updated successfully'
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                'Validation failed',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $e->errors()
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update teacher: ' . $e->getMessage());
            return $this->error(
                'Failed to update teacher',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/teachers/{id}",
     *     summary="Delete a teacher",
     *     description="Permanently deletes a teacher and their associated user account",
     *     tags={"Teachers"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the teacher to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Teacher deleted successfully"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teacher not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Teacher not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete teacher")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {
            $teacher = TeacherModel::with('user')->find($id);

            if (!$teacher) {
                return $this->error('Teacher not found', Response::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();

            // Delete the associated user
            $user = $teacher->user;
            $teacher->delete();
            $user->delete();

            DB::commit();

            return $this->success(
                null,
                'Teacher deleted successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete teacher: ' . $e->getMessage());
            return $this->error(
                'Failed to delete teacher',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
