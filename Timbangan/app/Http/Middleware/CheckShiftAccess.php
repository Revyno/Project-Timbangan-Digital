<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ShiftService;
use Illuminate\Support\Facades\Auth;

class CheckShiftAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            if (!ShiftService::canAccess($user)) {
                // If it's an AJAX/Livewire request, return error
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Bukan jam shift Anda.'], 403);
                }

                // Log out user and redirect with error
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Akses ditolak. Saat ini bukan jam shift Anda.');
            }
        }

        return $next($request);
    }
}
