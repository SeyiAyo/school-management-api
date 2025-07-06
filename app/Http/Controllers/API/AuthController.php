<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\ParentModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register new user with admin role",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Registered successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        // Create user with admin role
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        // Create token with admin ability
        $token = $user->createToken('token', ['role:admin'])->plainTextToken;

        return response()->json([
            'user' => $user,
            'role' => 'admin',
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login for all user types",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Logged in successfully"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Find user in the central users table
        $user = User::where('email', $request->email)->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        // Create token with role-specific ability
        $token = $user->createToken('token', ['role:' . $user->role])->plainTextToken;
        
        // Get the profile data based on role
        $profile = null;
        switch ($user->role) {
            case 'teacher':
                $profile = $user->teacher;
                break;
            case 'student':
                $profile = $user->student;
                break;
            case 'parent':
                $profile = $user->parent;
                break;
            default:
                $profile = null;
                break;
        }
        
        return response()->json([
            'user' => $user,
            'profile' => $profile,
            'role' => $user->role,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);

        // No user found with these credentials
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Response(response=200, description="Logged out successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request)
    {
        // Make sure we're using Laravel Sanctum for token management
        if ($request->user()) {
            // Revoke the token that was used to authenticate the current request
            $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }
}
