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
}
