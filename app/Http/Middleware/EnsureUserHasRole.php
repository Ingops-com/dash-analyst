<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // Normalize roles to lower-case and support comma-separated or variadic params
        $normalized = [];
        foreach ($roles as $r) {
            foreach (explode(',', (string) $r) as $piece) {
                $piece = trim(strtolower($piece));
                if ($piece !== '') $normalized[] = $piece;
            }
        }

        // If no roles specified, allow
        if (empty($normalized)) {
            return $next($request);
        }

        $userRole = strtolower((string) ($user->rol ?? ''));
        if (!in_array($userRole, $normalized, true)) {
            return redirect('/dashboard');
        }

        return $next($request);
    }
}
