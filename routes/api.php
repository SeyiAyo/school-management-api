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

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/admin/dashboard', [DashboardController::class, 'index']);

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

        // Other protected routes
        Route::post('/classes', [SchoolClass::class, 'store']);
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
