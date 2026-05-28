<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'supplier_id', 'nomor_plat', 'qr_code', 'asal'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
