<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Account is disabled.'], 403);
            }
            return redirect()->route('login')->withErrors(['username' => 'Account is disabled.']);
        }

        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Forbidden - Access Denied',
                    'detail' => "You do not have the required permission ({$permission}) to perform this action."
                ], 403);
            }
            abort(403, "Access Denied: Missing required permission [{$permission}].");
        }

        return $next($request);
    }
}
