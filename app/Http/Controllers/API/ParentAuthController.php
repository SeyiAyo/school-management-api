<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParentModel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ParentAuthController extends Controller
{
    // Authentication has been centralized to AuthController
    // This controller is kept for any future parent-specific functionality
}
