<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\Teacher;

class SubjectController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/api/subjects",
     *     summary="Get all subjects",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Subjects retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subjects retrieved successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="code", type="string"),
     *                     @OA\Property(property="created_by", type="integer"),
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
            $subjects = Subject::with(['creator', 'classes', 'teachers'])->get();
            return $this->success($subjects, 'Subjects retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving subjects: ' . $e->getMessage());
            return $this->internalServerError('Failed to retrieve subjects');
        }
    }

    /**
     * @OA\Post(
     *     path="/api/subjects",
     *     summary="Create a new subject",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code"},
     *             @OA\Property(property="name", type="string", example="Mathematics"),
     *             @OA\Property(property="code", type="string", example="MATH")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Subject created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subject created successfully"),
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
                'name' => 'required|string|max:255|unique:subjects,name',
                'code' => 'required|string|max:10|unique:subjects,code',
            ], [
                'name.required' => 'Subject name is required',
                'name.unique' => 'A subject with this name already exists',
                'code.required' => 'Subject code is required',
                'code.unique' => 'A subject with this code already exists',
                'code.max' => 'Subject code cannot exceed 10 characters',
            ]);

            DB::beginTransaction();

            $subject = Subject::create([
                'name' => $validatedData['name'],
                'code' => strtoupper($validatedData['code']),
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            $subject->load('creator');
            return $this->success($subject, 'Subject created successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating subject: ' . $e->getMessage());
            return $this->internalServerError('Failed to create subject');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/subjects/{subject}",
     *     summary="Get a specific subject",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subject",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subject retrieved successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Subject not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(Subject $subject)
    {
        try {
            $subject->load(['creator', 'classes.teacher.user', 'teachers.user']);
            return $this->success($subject, 'Subject retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving subject: ' . $e->getMessage());
            return $this->internalServerError('Failed to retrieve subject');
        }
    }

    /**
     * @OA\Put(
     *     path="/api/subjects/{subject}",
     *     summary="Update a subject",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subject",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subject updated successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Subject not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, Subject $subject)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:subjects,name,' . $subject->id,
                'code' => 'sometimes|required|string|max:10|unique:subjects,code,' . $subject->id,
            ], [
                'name.required' => 'Subject name is required',
                'name.unique' => 'A subject with this name already exists',
                'code.required' => 'Subject code is required',
                'code.unique' => 'A subject with this code already exists',
                'code.max' => 'Subject code cannot exceed 10 characters',
            ]);

            DB::beginTransaction();

            $subject->update([
                'name' => $validatedData['name'] ?? $subject->name,
                'code' => isset($validatedData['code']) ? strtoupper($validatedData['code']) : $subject->code,
            ]);

            DB::commit();

            $subject->load('creator');
            return $this->success($subject, 'Subject updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating subject: ' . $e->getMessage());
            return $this->internalServerError('Failed to update subject');
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/subjects/{subject}",
     *     summary="Delete a subject",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subject",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subject deleted successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Subject not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(Subject $subject)
    {
        try {
            DB::beginTransaction();

            // Detach all classes from this subject
            $subject->classes()->detach();
            
            // Delete the subject
            $subject->delete();

            DB::commit();

            return $this->success(null, 'Subject deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting subject: ' . $e->getMessage());
            return $this->internalServerError('Failed to delete subject');
        }
    }

    /**
     * @OA\Post(
     *     path="/api/subjects/{subject}/assign-to-class",
     *     summary="Assign a subject to a class with a teacher",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subject",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"class_id","teacher_id"},
     *             @OA\Property(property="class_id", type="integer", example=1),
     *             @OA\Property(property="teacher_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Subject assigned to class successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Subject assigned to class successfully"),
     *             @OA\Property(property="HttpStatusCode", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Subject not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function assignToClass(Request $request, Subject $subject)
    {
        try {
            $validatedData = $request->validate([
                'class_id' => 'required|exists:classes,id',
                'teacher_id' => 'required|exists:teachers,id',
            ], [
                'class_id.required' => 'Class selection is required',
                'class_id.exists' => 'Selected class does not exist',
                'teacher_id.required' => 'Teacher assignment is required',
                'teacher_id.exists' => 'Selected teacher does not exist',
            ]);

            DB::beginTransaction();

            // Check if subject is already assigned to this class
            $existingAssignment = $subject->classes()
                ->where('class_id', $validatedData['class_id'])
                ->exists();

            if ($existingAssignment) {
                return $this->error('Subject is already assigned to this class', 422);
            }

            // Assign subject to class with teacher
            $subject->classes()->attach($validatedData['class_id'], [
                'teacher_id' => $validatedData['teacher_id']
            ]);

            DB::commit();

            return $this->success(null, 'Subject assigned to class successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning subject to class: ' . $e->getMessage());
            return $this->internalServerError('Failed to assign subject to class');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/subjects/dropdown-options",
     *     summary="Get dropdown options for subject forms",
     *     tags={"Subjects"},
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
     *                     property="classes",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="teachers",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
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
            $classes = SchoolClass::with('teacher.user')
                ->get()
                ->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'grade' => $class->grade
                    ];
                });

            $teachers = Teacher::with('user')
                ->get()
                ->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->user->name,
                        'employee_id' => $teacher->employee_id
                    ];
                });

            $data = [
                'classes' => $classes,
                'teachers' => $teachers,
            ];

            return $this->success($data, 'Dropdown options retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving dropdown options: ' . $e->getMessage());
            return $this->internalServerError('Failed to retrieve dropdown options');
        }
    }
}
