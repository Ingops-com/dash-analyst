<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        if ($request->user()->rol !== $role) {
            return redirect('/dashboard');
        }

        return $next($request);
    }
}