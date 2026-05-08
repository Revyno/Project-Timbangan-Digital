<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'tipe',
        'shift',
        'shift_start',
        'shift_end',
        'shift_type',
        'session_locked',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'session_locked' => 'boolean',
        ];
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isOperator()
    {
        return $this->role === 'operator';
    }

    public function isFg()
    {
        return $this->tipe === 'fg';
    }

    public function isIncomingSingkong()
    {
        return $this->tipe === 'incoming_singkong';
    }

    public function isIncomingRmpm()
    {
        return $this->tipe === 'incoming_rmpm';
    }

    public function penimbangans()
    {
        return $this->hasMany(Penimbangan::class);
    }

    public function incomingSingkongs()
    {
        return $this->hasMany(IncomingSingkong::class);
    }

    public function incomingRmpms()
    {
        return $this->hasMany(IncomingRmpm::class);
    }

    /**
     * Check if the session is locked for the current shift.
     * If it was locked in a previous shift, it will be automatically unlocked.
     */
    public function isSessionLocked(): bool
    {
        if (!$this->session_locked) {
            return false;
        }

        // If it's an admin, never lock
        if ($this->isAdmin()) {
            return false;
        }

        if (!$this->shift_start) {
            // Fallback if no shift_start is set
            if ($this->updated_at->isToday()) {
                return true;
            }
            $this->update(['session_locked' => false]);
            return false;
        }

        $now = now();
        $parts = explode(':', $this->shift_start);
        $hour = (int)$parts[0];
        $minute = (int)($parts[1] ?? 0);
        $second = (int)($parts[2] ?? 0);

        // Determine the most recent shift start time relative to now
        $lastShiftStart = $now->copy()->setTime($hour, $minute, $second);

        // If the calculated time is in the future, it means the shift started yesterday
        if ($lastShiftStart > $now) {
            $lastShiftStart->subDay();
        }

        // If locked after or exactly at the start of the current shift, remain locked
        if ($this->updated_at >= $lastShiftStart) {
            return true;
        }

        // Locked in a previous shift, auto-unlock
        $this->update(['session_locked' => false]);
        return false;
    }
}
