<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;

class ShiftService
{
    /**
     * Check if a user is currently allowed to access the system.
     * Admins always have access.
     * 
     * @param User $user
     * @return bool
     */
    public static function canAccess(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // If shift times are not set, deny access by default or allow?
        // Usually, we deny if times are set and current time is outside.
        if (!$user->shift_start || !$user->shift_end) {
            return false;
        }

        // Lock check
        if ($user->isSessionLocked()) {
            return false;
        }

        $now = now()->format('H:i:s');
        $start = $user->shift_start;
        $end = $user->shift_end;

        // Handle night shifts (e.g. 22:00 to 06:00)
        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        } else {
            // Crosses midnight
            return $now >= $start || $now <= $end;
        }
    }

    /**
     * Get the active operator for the current time.
     * Searches for operators whose shift window is currently open.
     * 
     * @return User|null
     */
    public static function getActiveOperator(): ?User
    {
        $now = now()->format('H:i:s');

        // Prioritas Utama: operator yang sudah memulai sesi manual (cache aktif).
        // Sesi manual adalah bukti operator sedang bekerja, valid di luar jam shift.
        $unlocked = User::where('role', 'operator')
            ->where('session_locked', false)
            ->get();

        foreach ($unlocked as $op) {
            if (cache()->has("session_operator_{$op->id}")) {
                return $op;
            }
        }

        // Fallback (legacy): operator yang jam shift-nya mencakup 'now' dan tidak terkunci
        return User::where('role', 'operator')
            ->where('session_locked', false)
            ->where(function($query) use ($now) {
                $query->where(function($q) use ($now) {
                    $q->whereColumn('shift_start', '<=', 'shift_end')
                      ->where('shift_start', '<=', $now)
                      ->where('shift_end', '>=', $now);
                })
                ->orWhere(function($q) use ($now) {
                    // Over midnight case
                    $q->whereColumn('shift_start', '>', 'shift_end')
                      ->where(function($qq) use ($now) {
                          $qq->where('shift_start', '<=', $now)
                            ->orWhere('shift_end', '>=', $now);
                      });
                });
            })
            ->first();
    }
}
