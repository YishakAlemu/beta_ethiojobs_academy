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
        $otp = (string) random_int(100000, 999999);
        $user = User::create([
            'name'     => $fields['name'],
            'email'    => $fields['email'],
            'password' => Hash::make($fields['password']),
            'role'     => $fields['role'] ?? UserRole::STUDENT->value,
            'verification_code'            => $otp,
        'verification_code_expires_at' => now()->addMinutes(15),
        ]);
        (new SendVerificationCode($otp))->sendViaResend($user);
        // Generate Sanctum API token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please check your email for the verification code.',
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

    if (is_null($user->email_verified_at)) {
        return response()->json([
            'message'          => 'Your email address is not verified. Please verify your email before logging in.',
            'is_verified'      => false,
        ], 403);
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
    public function verifyEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'code'  => 'required|string|size:6',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    if ($user->verification_code !== $request->code) {
        return response()->json(['message' => 'Invalid verification code.'], 422);
    }

    if (now()->greaterThan($user->verification_code_expires_at)) {
        return response()->json(['message' => 'Verification code has expired. Please request a new one.'], 422);
    }

    // Mark user as verified & clear code
    $user->update([
        'email_verified_at'            => now(),
        'verification_code'            => null,
        'verification_code_expires_at' => null,
    ]);

    return response()->json([
        'message' => 'Email verified successfully! You can now log in.',
    ]);
}
public function resendCode(Request $request)
{
    $request->validate(['email' => 'required|email']);
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    if ($user->email_verified_at) {
        return response()->json(['message' => 'This account is already verified.'], 400);
    }

    $otp = (string) random_int(100000, 999999);
    $user->update([
        'verification_code' => $otp,
        'verification_code_expires_at' => now()->addMinutes(15),
    ]);

    // Send email via Mailtrap or Mail facade
    // ...

    return response()->json(['message' => 'A new verification code has been sent.']);
}
}