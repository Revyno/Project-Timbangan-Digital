<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeighLog extends Model
{
    use HasFactory;

    protected $fillable = ['driver_id', 'supplier_id', 'berat', 'device_id'];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
