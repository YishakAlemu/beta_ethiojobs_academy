<?php

namespace App\Http\Controllers;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\UserResource;
use Illuminate\Validation\Rules\Enum;
use OpenApi\Attributes as OA;
use App\Notifications\SendVerificationCode;
use Illuminate\Support\Str;
class AuthController extends Controller
{
    #[OA\Post(
        path: "/api/register",
        summary: "Register a new user or university",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Addis Ababa University"),
                    new OA\Property(property: "email", type: "string", example: "aau@example.com"),
                    new OA\Property(property: "password", type: "string", example: "password123"),
                    new OA\Property(property: "role", type: "string", enum: ["student", "university", "admin"], example: "university")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "User registered successfully"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    // 1. REGISTER USER
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => ['nullable', 'string', new Enum(UserRole::class)],
        ]);
       
        $user = User::create([
            'name'     => $fields['name'],
            'email'    => $fields['email'],
            'password' => Hash::make($fields['password']),
            'role'     => $fields['role'] ?? UserRole::STUDENT->value,
        ]);
        // Generate Sanctum API token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user'  => new UserResource($user),
            'token' => $token,
        ], 201);
    }
     #[OA\Post(
        path: "/api/login",
        summary: "Log in user and return token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", example: "aau@example.com"),
                    new OA\Property(property: "password", type: "string", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Login successful"),
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    public function users()
    {
        $users = User::all();
        return UserResource::collection($users);
        return response()->json($users, 200);
    }

    // 2. LOGIN USER
    public function login(Request $request)
    {
       $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

        $user = User::where('email', $credentials['email'])->first();

    if (!$user || !Hash::check($credentials['password'], $user->password)) {
        return response()->json(['message' => 'Invalid credentials.'], 401);
    }

   

        // Generate Sanctum API token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'  => new UserResource($user),
            'token' => $token,
        ], 200);
    }

    // 3. LOGOUT USER
    public function logout(Request $request)
    {
        // Delete current token being used
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ], 200);
    }
   

}