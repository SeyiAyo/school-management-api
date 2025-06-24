<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\Teacher;
use App\Http\Controllers\API\Student;
use App\Http\Controllers\API\Attendance;
use App\Http\Controllers\API\SchoolClass;


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

// Health Check Endpoint for CI/CD
Route::get('/health', function() {
    return response()->json(['status' => 'ok']);
});

// Admin Authentication Routes
Route::post('/admin/register', [AuthController::class, 'register']);
Route::post('/admin/login', [AuthController::class, 'login']);

// Admin Dashboard Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
    Route::apiResource('/teachers', Teacher::class);
    Route::apiResource('/students', Student::class);
    Route::post('/classes', [SchoolClass::class, 'store']);
    Route::post('/attendance', [Attendance::class, 'mark']);
});
