<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\EmailVerificationController;

Route::get('/', function () {
    return view('welcome');
});

// Email verification callback (signed URL)
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
