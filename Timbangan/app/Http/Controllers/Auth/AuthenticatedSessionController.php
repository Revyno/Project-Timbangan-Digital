<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     * Setelah autentikasi berhasil, cek apakah sesi operator masih terkunci
     * (masih dalam shift yang sama saat klik "Selesai").
     * isSessionLocked() akan otomatis unlock jika shift sudah berganti.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Cek apakah operator masih terkunci dalam shift yang sama
        if ($user->isOperator() && $user->isSessionLocked()) {
            // Logout karena shift belum berganti
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $shiftStart = $user->shift_start ?? null;
            $msg = $shiftStart
                ? "Sesi shift Anda masih terkunci. Login kembali diperbolehkan mulai jam {$shiftStart} pada shift berikutnya."
                : 'Sesi shift Anda masih terkunci. Silahkan login kembali pada shift berikutnya.';

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $msg]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
