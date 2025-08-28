<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\Teacher;
use App\Http\Controllers\API\Student;
use App\Http\Controllers\API\Attendance;
use App\Http\Controllers\API\SchoolClass;
use App\Http\Controllers\API\ParentController;
use App\Http\Controllers\API\SubjectController;
use App\Http\Controllers\API\EmailVerificationController;
use App\Http\Controllers\API\OnboardingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Apply CORS middleware to all API routes
Route::middleware(\App\Http\Middleware\DebugCorsMiddleware::class)->group(function () {
    // CORS test endpoint
    Route::get('/test-cors', function () {
        return response()->json([
            'message' => 'CORS is working!',
            'timestamp' => now()->toDateTimeString()
        ]);
    });

    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('teachers/dropdown-options', [Teacher::class, 'getDropdownOptions']);
    Route::get('students/dropdown-options', [Student::class, 'getDropdownOptions']);
    Route::get('classes/dropdown-options', [SchoolClass::class, 'getDropdownOptions']);
    Route::get('subjects/dropdown-options', [SubjectController::class, 'getDropdownOptions']);

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/admin/dashboard', [DashboardController::class, 'index']);

        // Resend email verification for authenticated user
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1');

        // Onboarding routes (email must be verified; controller enforces)
        Route::post('/onboarding/school-profile/step-1', [OnboardingController::class, 'step1']);
        Route::post('/onboarding/school-profile/step-2', [OnboardingController::class, 'step2']);
        Route::post('/onboarding/school-profile/complete', [OnboardingController::class, 'complete']);

        // Resource routes
        Route::apiResource('teachers', Teacher::class)->parameters([
            'teachers' => 'teacher'  // This tells Laravel to use 'teacher' as the parameter name for model binding
        ]);
        Route::apiResource('/students', Student::class)->parameters([
            'students' => 'student'
        ]);
        Route::apiResource('/parents', ParentController::class);
        Route::apiResource('/classes', SchoolClass::class)->parameters([
            'classes' => 'class'
        ]);
        Route::apiResource('/subjects', SubjectController::class);
        
        // Subject-Class assignment
        Route::post('/subjects/{subject}/assign-to-class', [SubjectController::class, 'assignToClass']);

        // Other protected routes
        Route::post('/attendance', [Attendance::class, 'mark']);
    });

    // Catch-all OPTIONS route for preflight requests
    Route::options('/{any}', function () {
        return response('', 204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE, PATCH')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization, Accept, X-Requested-With')
            ->header('Access-Control-Allow-Credentials', 'true');
    })->where('any', '.*');
});
