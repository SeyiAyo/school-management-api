<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParentModel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum', ['except' => ['index', 'show']]);
        $this->authorizeResource(ParentModel::class, 'parent');
    }

    /**
     * @OA\Get(
     *     path="/api/parents",
     *     summary="Get all parents",
     *     tags={"Parents"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Response(response=200, description="List of parents retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        $parents = ParentModel::all();
        return response()->json($parents);
    }

    /**
     * @OA\Post(
     *     path="/api/parents",
     *     summary="Create a new parent",
     *     tags={"Parents"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Parent created successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable',
            'address' => 'nullable',
        ]);
        
        // Generate a random password
        $password = Str::random(8);
        
        DB::beginTransaction();
        
        try {
            // Create user with parent role
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role' => 'parent',
            ]);
            
            // Create parent profile
            $parent = ParentModel::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
            
            DB::commit();
            
            // Return the parent data along with the plain text password
            return response()->json([
                'user' => $user,
                'parent' => $parent,
                'generated_password' => $password
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create parent', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/parents/{id}",
     *     summary="Get a specific parent",
     *     tags={"Parents"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Parent details retrieved successfully"),
     *     @OA\Response(response=404, description="Parent not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show(string $id)
    {
        $parent = ParentModel::find($id);
        
        if (!$parent) {
            return response()->json(['message' => 'Parent not found'], 404);
        }
        
        return response()->json($parent);
    }

    /**
     * @OA\Put(
     *     path="/api/parents/{id}",
     *     summary="Update a parent",
     *     tags={"Parents"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Parent updated successfully"),
     *     @OA\Response(response=404, description="Parent not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, string $id)
    {
        $parent = ParentModel::find($id);
        
        if (!$parent) {
            return response()->json(['message' => 'Parent not found'], 404);
        }
        
        $request->validate([
            'phone' => 'nullable',
            'address' => 'nullable',
        ]);
        
        $parent->update($request->only(['phone', 'address']));
        
        return response()->json($parent);
    }

    /**
     * @OA\Delete(
     *     path="/api/parents/{id}",
     *     summary="Delete a parent",
     *     tags={"Parents"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Parent deleted successfully"),
     *     @OA\Response(response=404, description="Parent not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy(string $id)
    {
        $parent = ParentModel::find($id);
        
        if (!$parent) {
            return response()->json(['message' => 'Parent not found'], 404);
        }
        
        // Delete the associated user as well
        $user = $parent->user;
        if ($user) {
            $user->delete();
        }
        
        $parent->delete();
        
        return response()->json(['message' => 'Parent deleted successfully']);
    }
}
