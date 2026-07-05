<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penimbangan extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_penimbangan',
        'produk_id',
        'user_id',
        'kode_produksi',
        'tanggal_expired',
        'berat',
        'device_id',
        'status',
    ];

    protected $casts = [
        'tanggal_penimbangan' => 'date',
        'tanggal_expired' => 'date',
        'berat' => 'decimal:3',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    // Accessor untuk menampilkan Kode Produksi tanpa suffix unik (#timestamp)
    public function getKodeProduksiDisplayAttribute()
    {
        return explode('#', $this->kode_produksi)[0];
    }
}
