<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Admin automatically bypasses role restrictions
        if ($request->user()->role === 'admin') {
            return $next($request);
        }

        // Standard role check: if current user role is not in the list of allowed roles, abort.
        if (!empty($roles) && !in_array($request->user()->role, $roles)) {
            abort(403, 'Unauthorized. Akses ditolak.');
        }

        return $next($request);
    }
}
