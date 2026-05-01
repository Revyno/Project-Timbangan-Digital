<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingRmpm extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_kedatangan',
        'petugas_penerima',
        'nama_barang',
        'jenis_barang',
        'asal',
        'nama_supplier',
        'no_surat',
        'nama_sopir',
        'nomor_plat',
        'total_qty',
        'kode_batch',
        'expired_date',
        'berat',
        'user_id',
        'device_id',
        'status',
    ];

    protected $casts = [
        'tanggal_kedatangan' => 'date',
        'expired_date' => 'date',
        'berat' => 'decimal:3',
    ];

    // Dropdown options from Google Form
    public static function namaBarangOptions(): array
    {
        return [
            'BAKING POWDER', 'BAKING SODA', 'BASIL', 'BAWANG PUTIH',
            'CHOCO FLAVOR', 'COKLAT POWDER', 'DAUN BAWANG', 'DMG / GMS',
            'FIBERSSE', 'GARAM', 'GULA KELAPA', 'GULA PASIR HALUS',
            'KALDU AYAM NON GARAM', 'KETUMBAR', 'KUNIR BUBUK', 'LADA BUBUK',
            'MAIZENA', 'MINYAK GORENG', 'NON DAIRY CREAMER', 'PATI KENTANG',
            'PATI MODIFIKASI', 'PATI SAGU', 'SOFTENER (SSL)', 'STTP',
            'TEPUNG BERAS', 'TEPUNG LL', 'VANILA BUBUK', 'XANTHAM GUM',
        ];
    }

    public static function asalOptions(): array
    {
        return ['Surabaya', 'Vendor/Supplier', 'Lainnya'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
