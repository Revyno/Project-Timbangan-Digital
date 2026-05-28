<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingSingkong extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal_penimbangan',
        'no_surat',
        'nama_supplier',
        'asal',
        'nama_sopir',
        'nomor_plat',
        'jenis_singkong',
        'kode_produksi',
        'berat',
        'user_id',
        'device_id',
        'status',
    ];

    protected $casts = [
        'tanggal_penimbangan' => 'date',
        'berat' => 'decimal:3',
    ];

    // Dropdown options
    public static function jenisOptions(): array
    {
        return ['Cimanggu', 'Daplang', 'Farokha', 'Kaspro', 'Konsumsi'];
    }

    public static function asalOptions(): array
    {
        return [
            'Bondowoso', 'Cengkrong', 'Coban Joyo', 'Kelangrong', 'Klinter',
            'Malang', 'Mangguan', 'Pasrepan', 'Pasuruan', 'Pati',
            'Sumber Benteng', 'Tambak', 'Tuban', 'Tundosuruh',
        ];
    }

    public static function supplierOptions(): array
    {
        return [
            'Debby', 'Edi Santoso', 'Gonta Bagus', 'H.Munir', 'Lahan Pabrik',
            'Mt.Faizin', 'Mt.Gofur', 'Mt.Gus Ali', 'Mt.Gus Aying', 'Mt.H. Mustain',
            'Mt Mas Ali', 'Mt.Munip', 'Mt.Rohani', 'Mt.Subhan', 'Mt.Winarto',
            'Rita', 'Sutalim', 'Untung Sedewa', 'Yanuar',
        ];
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
