<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SchoolClass as SchoolClassModel;
use App\Models\Teacher;
use App\Models\Student;

class SchoolClass extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['getDropdownOptions']);
    }

    /**
     * @OA\Get(
     *     path="/api/classes",
     *     summary="Get all classes",
     *     tags={"Classes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Classes retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Classes retrieved successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="teacher_id", type="integer"),
     *                     @OA\Property(property="capacity", type="integer"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        try {
            $classes = SchoolClassModel::with(['teacher.user', 'students.user'])->get();
            return $this->success($classes, 'Classes retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving classes: ' . $e->getMessage());
            return $this->internalServerError('Failed to retrieve classes');
        }
    }

    /**
     * @OA\Post(
     *     path="/api/classes",
     *     summary="Create a new class",
     *     tags={"Classes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","grade"},
     *             @OA\Property(property="name", type="string", example="Class 10A"),
     *             @OA\Property(property="grade", type="integer", minimum=1, maximum=12, example=10, description="Grade level (1-12), must be unique"),
     *             @OA\Property(property="teacher_id", type="integer", example=1),
     *             @OA\Property(property="capacity", type="integer", example=30),
     *             @OA\Property(property="students", type="array", @OA\Items(type="integer"), example={1,2,3})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Class created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Class created successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=201)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:classes,name',
                'grade' => 'required|' . SchoolClassModel::getGradeValidationRule(),
                'teacher_id' => 'nullable|exists:teachers,id',
                'capacity' => 'nullable|integer|min:1|max:100',
                'students' => 'nullable|array',
                'students.*' => 'exists:students,id',
            ], [
                'name.required' => 'Class name is required',
                'name.unique' => 'A class with this name already exists',
                'grade.required' => 'Grade is required',
                'grade.integer' => 'Grade must be a number',
                'grade.min' => 'Grade must be between 1 and 12',
                'grade.max' => 'Grade must be between 1 and 12',
                'teacher_id.exists' => 'Selected teacher does not exist',
                'capacity.min' => 'Class capacity must be at least 1',
                'capacity.max' => 'Class capacity cannot exceed 100 students',
            ]);

            DB::beginTransaction();

            $class = SchoolClassModel::create([
                'name' => $validatedData['name'],
                'grade' => $validatedData['grade'],
                'teacher_id' => $validatedData['teacher_id'] ?? null,
                'capacity' => $validatedData['capacity'] ?? 30,
            ]);

            // Attach students if provided
            if (!empty($validatedData['students'])) {
                // Check capacity limit
                if ($class->capacity && count($validatedData['students']) > $class->capacity) {
                    DB::rollBack();
                    return $this->error('Cannot enroll more students than class capacity allows', 422);
                }
                $class->students()->attach($validatedData['students']);
            }

            DB::commit();

            $class->load(['teacher.user', 'students.user']);
            return $this->success($class, 'Class created successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating class: ' . $e->getMessage());
            return $this->internalServerError('Failed to create class');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/classes/{class}",
     *     summary="Get a specific class",
     *     tags={"Classes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="class",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Class retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Class retrieved successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Class not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(SchoolClassModel $class)
    {
        try {
            $class->load(['teacher.user', 'students.user']);
            return $this->success($class, 'Class retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving class: ' . $e->getMessage());
            return $this->internalServerError('Failed to retrieve class');
        }
    }

    /**
     * @OA\Put(
     *     path="/api/classes/{class}",
     *     summary="Update a class",
     *     tags={"Classes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="class",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="grade", type="integer", minimum=1, maximum=12, description="Grade level (1-12), must be unique"),
     *             @OA\Property(property="teacher_id", type="integer"),
     *             @OA\Property(property="capacity", type="integer"),
     *             @OA\Property(property="students", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Class updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Class updated successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Class not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, SchoolClassModel $class)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:classes,name,' . $class->id,
                'grade' => 'nullable|' . SchoolClassModel::getGradeValidationRuleForUpdate($class->id),
                'teacher_id' => 'sometimes|required|exists:teachers,id',
                'capacity' => 'nullable|integer|min:1|max:100',
                'students' => 'nullable|array',
                'students.*' => 'exists:students,id',
            ], [
                'name.required' => 'Class name is required',
                'name.unique' => 'A class with this name already exists',
                'teacher_id.required' => 'Teacher assignment is required',
                'teacher_id.exists' => 'Selected teacher does not exist',
                'capacity.min' => 'Class capacity must be at least 1',
                'capacity.max' => 'Class capacity cannot exceed 100 students',
            ]);

            DB::beginTransaction();

            // Update class details
            $class->update([
                'name' => $validatedData['name'] ?? $class->name,
                'grade' => $validatedData['grade'] ?? $class->grade,
                'teacher_id' => $validatedData['teacher_id'] ?? $class->teacher_id,
                'capacity' => $validatedData['capacity'] ?? $class->capacity,
            ]);

            // Update student enrollments if provided
            if (array_key_exists('students', $validatedData)) {
                $students = $validatedData['students'] ?? [];

                // Check capacity limit
                if ($class->capacity && count($students) > $class->capacity) {
                    DB::rollBack();
                    return $this->error('Cannot enroll more students than class capacity allows', 422);
                }

                $class->students()->sync($students);
            }

            DB::commit();

            $class->load(['teacher.user', 'students.user']);
            return $this->success($class, 'Class updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating class: ' . $e->getMessage());
            return $this->internalServerError('Failed to update class');
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/classes/{class}",
     *     summary="Delete a class",
     *     tags={"Classes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="class",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Class deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Class deleted successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Class not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(SchoolClassModel $class)
    {
        try {
            DB::beginTransaction();

            // Detach all students from the class
            $class->students()->detach();

            // Delete the class
            $class->delete();

            DB::commit();

            return $this->success(null, 'Class deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting class: ' . $e->getMessage());
            return $this->internalServerError('Failed to delete class');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/classes/dropdown-options",
     *     summary="Get dropdown options for class forms",
     *     tags={"Classes"},
     *     @OA\Response(
     *         response=200,
     *         description="Dropdown options retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dropdown options retrieved successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(
     *                     property="teachers",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="students",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="grades",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="value", type="integer"),
     *                         @OA\Property(property="label", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getDropdownOptions()
    {
        try {
            $teachers = Teacher::with('user')
                ->get()
                ->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->user->name,
                        'employee_id' => $teacher->employee_id
                    ];
                });

            $students = Student::with('user')
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->user->name,
                        'student_id' => $student->student_id
                    ];
                });

            // Get all available grades (1-12)
            $availableGrades = SchoolClassModel::getAvailableGrades();

            $data = [
                'teachers' => $teachers,
                'students' => $students,
                'grades' => $availableGrades,
            ];

            return $this->success($data, 'Dropdown options retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving dropdown options: ' . $e->getMessage());
            return $this->internalServerError('Failed to retrieve dropdown options');
        }
    }
}
