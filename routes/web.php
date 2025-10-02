<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\EmailVerificationController;

Route::get('/', function () {
    return view('welcome');
});
