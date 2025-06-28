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
    /**
     * @OA\Post(
     *     path="/api/parent/login",
     *     summary="Parent login",
     *     tags={"Parent Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Find user in the central users table with parent role
        $user = User::where('email', $request->email)
                    ->where('role', 'parent')
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('token', ['role:parent'])->plainTextToken;
        $parent = $user->parent;

        return response()->json([
            'user' => $user,
            'parent' => $parent,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/parent/logout",
     *     summary="Parent logout",
     *     tags={"Parent Authentication"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Response(response=200, description="Logout successful"),
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
