<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\Teacher;
use App\Http\Controllers\API\Student;
use App\Http\Controllers\API\Attendance;
use App\Http\Controllers\API\SchoolClass;
use App\Http\Controllers\API\StudentAuthController;
use App\Http\Controllers\API\TeacherAuthController;
use App\Http\Controllers\API\ParentAuthController;
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

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']); // Admin registration only
Route::post('/login', [AuthController::class, 'login']); // Unified login for all user types
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']); // Unified logout

// Admin Dashboard Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('/teachers', Teacher::class);
    Route::apiResource('/students', Student::class);
    Route::apiResource('/parents', ParentController::class);
    Route::post('/classes', [SchoolClass::class, 'store']);
    Route::post('/attendance', [Attendance::class, 'mark']);
});

// CORS: Catch-all OPTIONS route for preflight requests
Route::options('/{any}', function () {
    return response('', 204)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
        ->header('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');
})->where('any', '.*');

// CORS test route with explicit headers
Route::get('/cors-test', function () {
    return response()
        ->json(['message' => 'CORS OK'])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
        ->header('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');
});
