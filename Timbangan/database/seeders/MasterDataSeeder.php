<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Debby'],
            ['name' => 'Edi Santoso'],
            ['name' => 'Gonta Bagus'],
            ['name' => 'H. Munir'],
            ['name' => 'Lahan Pabrik'],
            ['name' => 'Mt. Faizin'],
            ['name' => 'Mt. Gofur'],
            ['name' => 'Mt. Gus Ali'],
            ['name' => 'Mt. Gus Aying'],
            ['name' => 'Mt. H. Mustain'],
            ['name' => 'Mt. Mas Ali'],
            ['name' => 'Mt. Munip'],
            ['name' => 'Mt. Rohani'],
            ['name' => 'Mt. Subhan'],
            ['name' => 'Mt. Winarto'],
            ['name' => 'Ritu'],
            ['name' => 'Sutalim'],
            ['name' => 'Untung Sedaya'],
            ['name' => 'Yanuar'],
        ];

        foreach ($suppliers as $s) {
            $supplier = Supplier::create($s);

            // Create 2 drivers per supplier with plate numbers
            Driver::create([
                'name' => 'Sopir A ' . $supplier->name,
                'supplier_id' => $supplier->id,
                'nomor_plat' => 'L ' . rand(1000, 9999) . ' ABC',
                'qr_code' => 'DRV-' . strtoupper(Str::random(8)),
            ]);

            Driver::create([
                'name' => 'Sopir B ' . $supplier->name,
                'supplier_id' => $supplier->id,
                'nomor_plat' => 'W ' . rand(1000, 9999) . ' XYZ',
                'qr_code' => 'DRV-' . strtoupper(Str::random(8)),
            ]);
        }

        // ============================================================
        // RMPM — Nama Barang Options (from Google Form)
        // ============================================================
        $namaBarangRmpm = [
            'BAKING POWDER',
            'BAKING SODA',
            'BASIL',
            'BAWANG PUTIH',
            'CHOCO FLAVOR',
            'COKLAT POWDER',
            'DAUN BAWANG',
            'DMG / GMS',
            'FIBERSSE',
            'GARAM',
            'GULA KELAPA',
            'GULA PASIR HALUS',
            'KALDU AYAM NON GARAM',
            'KETUMBAR',
            'KUNIR BUBUK',
            'LADA BUBUK',
            'MAIZENA',
            'MINYAK GORENG',
            'NON DAIRY CREAMER',
            'PATI KENTANG',
            'PATI MODIFIKASI',
            'PATI SAGU',
            'SOFTENER (SSL)',
            'STTP',
            'TEPUNG BERAS',
            'TEPUNG LL',
            'VANILA BUBUK',
            'XANTHAM GUM',
        ];

        foreach ($namaBarangRmpm as $nama) {
            DB::table('rmpm_items')->updateOrInsert(
                ['type' => 'nama_barang', 'nama_barang' => $nama],
                ['type' => 'nama_barang', 'nama_barang' => $nama, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // ============================================================
        // RMPM — Asal Options (from Google Form)
        // ============================================================
        $asalRmpm = [
            'Surabaya',
            'Vendor/Supplier',
        ];

        foreach ($asalRmpm as $asal) {
            DB::table('rmpm_items')->updateOrInsert(
                ['type' => 'asal', 'nama_barang' => $asal],
                ['type' => 'asal', 'nama_barang' => $asal, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
