<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role->value ?? $user->role, $roles)) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to perform this action.'
            ], 403);
        }
        // Check if logged-in user's role string exists in allowed roles
        if (!$user || !in_array($user->role->value, $roles)) {
            return response()->json([
                'message' => 'Access denied. Unauthorized role.'
            ], 403);
        }

        return $next($request);
    }
}