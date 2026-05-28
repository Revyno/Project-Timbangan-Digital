<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'device_code',
        'device_name',
        'device_token',
        'current_product_id',
        'last_online',
        'is_active',
    ];

    protected $casts = [
        'last_online' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function penimbangans()
    {
        return $this->hasMany(Penimbangan::class);
    }
}
