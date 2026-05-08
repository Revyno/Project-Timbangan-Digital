<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Model
{
    //
    protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'shift',
    ];

    protected $hidden = [
    'password',
    'remember_token',
    ];

    protected $casts = [
    'email_verified_at' => 'datetime',
    ];



    public function penimbangans()
{
    return $this->hasMany(Penimbangans::class);
}

    public function isOperator()
    {
        return $this->role === 'operator';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isShiftActive()
    {
        $currentHour = now()->hour;

        if ($this->shift === 'pagi') {
            return $currentHour >= 6 && $currentHour < 14;
        } elseif ($this->shift === 'sore') {
            return $currentHour >= 14 && $currentHour < 22;
        } elseif ($this->shift === 'malam') {
            return $currentHour >= 22 || $currentHour < 6;
        }

        return false;
    }

    public function getShiftEndTime()
    {
        if ($this->shift === 'pagi') {
            return now()->setTime(14, 0, 0);
        } elseif ($this->shift === 'sore') {
            return now()->setTime(22, 0, 0);
        } elseif ($this->shift === 'malam') {
            $endTime = now()->setTime(6, 0, 0);
            if (now()->hour >= 22) {
                $endTime->addDay();
            }
            return $endTime;
        }

        return null;
    }
}