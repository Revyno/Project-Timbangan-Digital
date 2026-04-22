<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Device_iots extends Model
{
    //
    protected $fillable = [
        'device_code',
        'device_name',
        'device_token',
        'last_online',
        'is_active',
        'last_active_at',
    ];

    public function penimbangans()
    {
        return $this->hasMany(Penimbangans::class);
    }
}