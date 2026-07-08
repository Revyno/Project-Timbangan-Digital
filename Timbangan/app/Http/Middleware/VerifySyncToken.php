<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Melindungi endpoint ingest sync di server ONLINE. Hanya menerima request
 * yang membawa header X-Sync-Token yang cocok dengan config('hmi.online.token').
 * Menolak juga bila server ini bukan berperan sebagai 'online'.
 */
class VerifySyncToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('hmi.role') !== 'online') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Server ini tidak menerima sync (APP_ROLE bukan online).',
            ], 403);
        }

        $expected = (string) config('hmi.online.token');
        $provided = (string) $request->header('X-Sync-Token', '');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token sync tidak valid.',
            ], 401);
        }

        return $next($request);
    }
}
