<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        if (! $request->user()->is_active) {
            auth()->logout();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Account is disabled.'], 403);
            }
            return redirect()->route('login')->withErrors(['username' => 'Account is disabled.']);
        }

        if (! in_array($request->user()->role->name, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Operation not permitted.'], 403);
            }
            abort(403, 'Operation not permitted.');
        }

        return $next($request);
    }
}
