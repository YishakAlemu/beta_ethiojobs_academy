<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Http\Resources\LessonResource;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use App\Traits\EncryptsId;
use Illuminate\Support\Facades\Gate;
class LessonController extends Controller
{
    #[OA\Get(
        path: "/api/lessons",
        summary: "Get all lessons",
        tags: ["Lessons"],
        responses: [
            new OA\Response(response: 200, description: "List of all lessons")
        ]
    )]
    // 1. GET ALL
    public function index()
    {
        $lessons = Lesson::orderBy('order')->get();
        return LessonResource::collection($lessons); // Wraps collection with hashed IDs
    }
      #[OA\Post(
        path: "/api/lessons",
        summary: "Create a new lesson (University/Admin only)",
        tags: ["Lessons"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Introduction to Laravel Routing"),
                    new OA\Property(property: "slug", type: "string", example: "introduction-to-laravel-routing"),
                    new OA\Property(property: "description", type: "string", example: "Learn routes and controllers."),
                    new OA\Property(property: "content", type: "string", example: "# Welcome to the Lesson"),
                    new OA\Property(property: "video_url", type: "string", example: "https://cdn.example.com/video.mp4"),
                    new OA\Property(property: "duration_in_seconds", type: "integer", example: 360),
                    new OA\Property(property: "order", type: "integer", example: 1),
                    new OA\Property(property: "is_published", type: "boolean", example: true),
                    new OA\Property(property: "is_free_preview", type: "boolean", example: false)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Lesson created successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Unauthorized role")
        ]
    )]
    // 2. CREATE
    public function store(Request $request)
    {
        if (Gate::denies('manage-lessons')) {
        return response()->json(['message' => 'Unauthorized action.'], 403);
    }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ]);
        if (empty($validated['slug'])) {
        $validated['slug'] = Str::slug($validated['title']);
    }

        $lesson = Lesson::create($validated);

        return new LessonResource($lesson);
    }

    // 3. GET SINGLE
   public function show($id)
    {
        //  Decrypt the ID
        $decryptedId = Lesson::decryptId($id);

        if (!$decryptedId) {
            return response()->json(['message' => 'Invalid or tampered ID provided.'], 400);
        }

        $lesson = Lesson::findOrFail($decryptedId);

        return new LessonResource($lesson);
    }
    #[OA\Put(
        path: "/api/lessons/{id}",
        summary: "Update an existing lesson (University/Admin only)",
        tags: ["Lessons"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Encrypted Lesson ID",
                schema: new OA\Schema(type: "string")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Updated Routing Guide"),
                    new OA\Property(property: "description", type: "string", example: "Updated lesson details."),
                    new OA\Property(property: "is_published", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Lesson updated successfully"),
            new OA\Response(response: 400, description: "Invalid or tampered ID"),
            new OA\Response(response: 404, description: "Lesson not found")
        ]
    )]
    // 4.UPDATE
 public function update(Request $request, $id)
{
    // 1. Decrypt the incoming encrypted ID string
    $decryptedId = Lesson::decryptId($id);

    if (!$decryptedId) {
        return response()->json([
            'message' => 'Invalid or tampered ID provided.'
        ], 400);
    }

    // 2. Find the lesson or throw a 404
    $lesson = Lesson::findOrFail($decryptedId);

    // 3. Validate input (ignoring unique constraint on current lesson's slug)
    $validated = $request->validate([
        'title'               => 'sometimes|required|string|max:255',
        'slug'                => 'sometimes|nullable|string|unique:lessons,slug,' . $lesson->id,
        'description'         => 'nullable|string',
        'content'             => 'nullable|string',
        'video_url'           => 'nullable|url',
        'duration_in_seconds' => 'nullable|integer',
        'order'               => 'nullable|integer',
        'is_published'        => 'nullable|boolean',
        'is_free_preview'     => 'nullable|boolean',
    ]);

    // 4. Update slug automatically if title was updated and no slug was provided
    if (isset($validated['title']) && empty($validated['slug'])) {
        $validated['slug'] = Str::slug($validated['title']);
    }

    // 5. Save changes
    $lesson->update($validated);

    // 6. Return updated resource
    return new LessonResource($lesson);
}
    // 5. DELETE
  // 5. DELETE
public function destroy($id)
{
    // 1. Gate check for authorization
    if (Gate::denies('manage-lessons')) {
        return response()->json(['message' => 'Unauthorized action.'], 403);
    }

    // 2. Decrypt ID
    $decryptedId = Lesson::decryptId($id);

    if (!$decryptedId) {
        return response()->json(['message' => 'Invalid or tampered ID provided.'], 400);
    }

    // 3. Find and Delete
    $lesson = Lesson::findOrFail($decryptedId);
    $lesson->delete(); // <-- This will work once the Observer/Model event is fixed above

    return response()->json(['message' => 'Lesson deleted successfully']);
}
}