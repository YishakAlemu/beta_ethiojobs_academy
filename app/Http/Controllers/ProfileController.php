<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    #[OA\Get(
        path: "/api/profile",
        summary: "Get current user profile with role details",
        tags: ["Profile"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Profile retrieved"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function show(Request $request)
    {
       $user = $request->user()->load(['studentProfile', 'universityProfile', 'adminProfile']);
    return new UserResource($user);
    }

    #[OA\Put(
        path: "/api/profile",
        summary: "Update profile based on user role",
        tags: ["Profile"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Updated Name"),
                    new OA\Property(property: "field_of_study", type: "string", example: "Software Engineering"),
                    new OA\Property(property: "institution", type: "string", example: "AAU"),
                    new OA\Property(property: "website", type: "string", example: "https://aau.edu.et"),
                    new OA\Property(property: "location", type: "string", example: "Addis Ababa"),
                    new OA\Property(property: "description", type: "string", example: "Leading University in Ethiopia")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Profile updated successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function update(Request $request)
    {
        $user = $request->user();
        $roleValue = $user->role?->value ?? $user->role;

        // Common user updates
        $userValidated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);
        if (!empty($userValidated)) {
            $user->update($userValidated);
        }

        // Role-specific updates
        if ($roleValue === UserRole::STUDENT->value) {
            $studentData = $request->validate([
                'field_of_study' => 'nullable|string|max:255',
                'institution'    => 'nullable|string|max:255',
                'bio'            => 'nullable|string',
            ]);

            $user->studentProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $studentData
            );
        } elseif ($roleValue === UserRole::UNIVERSITY->value) {
            $universityData = $request->validate([
                'website'     => 'nullable|url|max:255',
                'location'    => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ]);

            $user->universityProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $universityData
            );
        }
        elseif ($roleValue === UserRole::ADMIN->value) {
        $adminData = $request->validate([
            'department'   => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'admin_level'  => 'nullable|string|max:255',
        ]);

        $user->adminProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $adminData
        );
    }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => new UserResource($user->fresh(['studentProfile', 'universityProfile', 'adminProfile'])),
        ]);
    }
}
