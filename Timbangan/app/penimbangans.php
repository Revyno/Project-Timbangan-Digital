<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class penimbangans extends Model
{
      protected $fillable = [
        'tanggal',
        'produk_id',
        'user_id',
        'kode_produksi',
        'tanggal_expired',
        'berat',
        'selisih',
        'device_id',
        'status',
    ];

    // RELASI
    public function produk()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device_iots::class);
    }

}
