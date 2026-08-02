<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // 1. Ensure user is authenticated
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // 2. Extract role string (whether $user->role is a PHP Enum or string)
        $userRole = $user->role instanceof \BackedEnum 
            ? $user->role->value 
            : (string) $user->role;

        // 3. Verify user's role exists in allowed $roles array
        if (!in_array($userRole, $roles, true)) {
            return response()->json([
                'message' => 'Access denied. Unauthorized role.'
            ], 403);
        }

        return $next($request);
    }
}