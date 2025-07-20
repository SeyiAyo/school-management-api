<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student as StudentModel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class Student extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum', ['except' => ['index', 'getDropdownOptions']]);
        $this->authorizeResource(StudentModel::class, 'student');
    }
    /**
     * @OA\Get(
     *     path="/api/students",
     *     summary="Get all students",
     *     description="Retrieves a list of all students with their user details",
     *     tags={"Students"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="List of students retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Students retrieved successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Student")
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
     *             @OA\Property(property="message", type="string", example="Failed to retrieve students")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $students = StudentModel::with('user')->get();
            return $this->success(
                $students,
                'Students retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve students: ' . $e->getMessage());
            return $this->error(
                'Failed to retrieve students',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/students",
     *     summary="Create a new student",
     *     description="Creates a new student with the provided information and generates a random password",
     *     tags={"Students"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Student data",
     *         @OA\JsonContent(
     *             required={"name", "email"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="student@example.com"),
     *             @OA\Property(property="phone", type="string", maxLength=20, example="+1234567890"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="2005-01-15"),
     *             @OA\Property(property="address", type="string", maxLength=500, example="123 Main St, City, Country"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, example="male"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=1, description="ID of the parent if applicable")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Student created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="student@example.com")
     *                 ),
     *                 @OA\Property(property="student", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="phone", type="string", example="+1234567890"),
     *                     @OA\Property(property="gender", type="string", example="male")
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
     *                 @OA\Property(property="parent_id", type="array",
     *                     @OA\Items(type="string", example="The selected parent does not exist.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create student")
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
                'phone' => 'nullable|string|regex:/^[\+]?[1-9]?[0-9]{7,15}$/|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'address' => 'nullable|string|max:500',
                'gender' => 'nullable|in:male,female,other',
                'parent_id' => 'nullable|exists:parents,id',
            ], [
                'phone.regex' => 'The phone number format is invalid. Please enter a valid phone number (7-15 digits, optionally starting with + and country code).',
                'parent_id.exists' => 'The selected parent does not exist.'
            ]);

            // Generate a random password
            $password = Str::random(8);

            DB::beginTransaction();

            // Create user with student role
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($password),
                'role' => 'student',
            ]);

            // Create student profile
            $student = StudentModel::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
            ]);

            DB::commit();

            return $this->success(
                [
                    'user' => $user->only(['id', 'name', 'email']),
                    'student' => $student->only(['id', 'phone', 'gender']),
                    'generated_password' => $password
                ],
                'Student created successfully',
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
            Log::error('Failed to create student: ' . $e->getMessage());
            return $this->error(
                'Failed to create student',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/students/{id}",
     *     summary="Get a specific student",
     *     description="Retrieves detailed information about a specific student including their user details",
     *     tags={"Students"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the student to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student retrieved successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200),
     *             @OA\Property(property="data", ref="#/components/schemas/StudentWithUser")
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
     *         response=404,
     *         description="Student not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve student")
     *         )
     *     )
     * )
     */
    public function show(StudentModel $student)
    {
        try {
            // The student is already loaded via route model binding
            // Just eager load the user relationship
            $student->load('user');

            return $this->success(
                $student,
                'Student retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Failed to retrieve student: ' . $e->getMessage());
            return $this->error(
                'Failed to retrieve student',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/students/{id}",
     *     summary="Update a student",
     *     description="Updates student information including user details",
     *     tags={"Students"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the student to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="2005-01-15"),
     *             @OA\Property(property="address", type="string", example="123 Main Street"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, example="male"),
     *             @OA\Property(property="parent_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student updated successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200),
     *             @OA\Property(property="data", ref="#/components/schemas/StudentWithUser")
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
     *         response=404,
     *         description="Student not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update student")
     *         )
     *     )
     * )
     */
    public function update(Request $request, StudentModel $student)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $student->user_id,
                'phone' => 'nullable|string|regex:/^[\+]?[1-9]?[0-9]{7,15}$/|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'address' => 'nullable|string|max:500',
                'gender' => 'nullable|in:male,female,other',
                'parent_id' => 'nullable|exists:parents,id',
            ], [
                'phone.regex' => 'The phone number format is invalid. Please enter a valid phone number (7-15 digits, optionally starting with + and country code).',
            ]);

            DB::beginTransaction();

            // Update user data if provided
            if (isset($validated['name']) || isset($validated['email'])) {
                $userUpdateData = collect($validated)
                    ->only(['name', 'email'])
                    ->filter()
                    ->toArray();

                if (!empty($userUpdateData)) {
                    $student->user->update($userUpdateData);
                }
            }

            // Update student data (exclude user-related fields)
            $studentUpdateData = collect($validated)
                ->except(['name', 'email'])
                ->filter() // Remove null values
                ->toArray();

            if (!empty($studentUpdateData)) {
                $student->update($studentUpdateData);
            }

            DB::commit();

            // Reload the student with user data
            $student->load('user');

            return $this->success(
                $student,
                'Student updated successfully'
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error(
                'Validation failed',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $e->errors()
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update student: ' . $e->getMessage());
            return $this->error(
                'Failed to update student',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/students/{id}",
     *     summary="Delete a student",
     *     description="Deletes a student and their associated user account",
     *     tags={"Students"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the student to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student deleted successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200)
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
     *         response=404,
     *         description="Student not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete student")
     *         )
     *     )
     * )
     */
    public function destroy(StudentModel $student)
    {
        try {
            DB::beginTransaction();

            // Delete the associated user account
            $student->user->delete();

            // The student record will be deleted via cascade or we can delete it explicitly
            $student->delete();

            DB::commit();

            return $this->success(
                null,
                'Student deleted successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete student: ' . $e->getMessage());
            return $this->error(
                'Failed to delete student',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/students/dropdown-options",
     *     summary="Get dropdown options for student creation",
     *     description="Returns available options for gender and parent selection",
     *     tags={"Students"},
     *     @OA\Response(
     *         response=200,
     *         description="Dropdown options retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dropdown options retrieved successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="genders", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="value", type="string", example="male"),
     *                         @OA\Property(property="label", type="string", example="Male")
     *                     )
     *                 ),
     *                 @OA\Property(property="parents", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve dropdown options")
     *         )
     *     )
     * )
     */
    public function getDropdownOptions()
    {
        try {
            $genders = [
                ['value' => 'male', 'label' => 'Male'],
                ['value' => 'female', 'label' => 'Female'],
                ['value' => 'other', 'label' => 'Other'],
            ];

            // Get available parents (assuming ParentModel exists)
            $parents = [];
            try {
                if (class_exists('App\\Models\\ParentModel')) {
                    $parents = \App\Models\ParentModel::with('user')
                        ->get()
                        ->map(function ($parent) {
                            return [
                                'id' => $parent->id,
                                'name' => $parent->user->name ?? 'Unknown Parent'
                            ];
                        })
                        ->toArray();
                }
            } catch (\Exception $e) {
                Log::warning('Could not load parents for dropdown: ' . $e->getMessage());
                $parents = [];
            }

            return $this->success([
                'genders' => $genders,
                'parents' => $parents
            ], 'Dropdown options retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve student dropdown options: ' . $e->getMessage());
            return $this->error(
                'Failed to retrieve dropdown options',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
