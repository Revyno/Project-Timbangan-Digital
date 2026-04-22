<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. ADMIN
        User::create([
            'name' => 'Admin System',
            'email' => 'admin@test.com',
            'password' => Hash::make('admin@test.com'),
            'role' => 'admin',
        ]);

        // 2. OPERATORS
        $operators = [
            ['name' => 'Hamimah', 'email' => 'hamimah@test.com','password' => 'operator1', 'shift' => '1'],
            ['name' => 'Luknan', 'email' => 'luknan@test.com','password' => 'operator2', 'shift' => '2'],
            ['name' => 'Zahra', 'email' => 'zahra@test.com','password' => 'operator3', 'shift' => '3'],
            ['name' => 'Zahro', 'email' => 'zahro@test.com','password' => 'operator4', 'shift' => '1'],
        ];

        foreach ($operators as $op) {
            User::create([
                'name' => $op['name'],
                'email' => $op['email'],
                'password' => Hash::make('password'),
                'role' => 'operator',
                'shift' => $op['shift'],
            ]);
        }

        // 3. PRODUKS (Raw Materials)
        $produks = [
            'Baking Powder', 'Baking Soda', 'Basil', 'Bawang Putih', 'Choco Flavor',
            'Coklat Powder', 'Daun Bawang', 'DMG/GMS', 'Fibersse', 'Garam',
            'Gula Kelapa', 'Gula Pasir Halus', 'Kaldu Ayam No Garam', 'Ketumbar',
            'Kunir Bubuk', 'Lada Bubuk', 'Maizena', 'Minyak Goreng', 'Non Dairy Creamer',
            'Pati Kentang', 'Pati Modifikasi', 'Pati Sagu', 'Softener', 'STTP',
            'Tepung Beras', 'Tepung LL', 'Vanila Bubuk', 'Xantham Gum'
        ];

        foreach ($produks as $p) {
            Produk::create([
                'nama_produk' => $p,
                'target_berat' => 0, // Default 0 as per industrial production raw material
            ]);
        }

        // 4. DEVICE
        Device::create([
            'device_code' => 'ARD-001',
            'device_name' => 'Timbangan Produksi 1',
            'device_token' => 'token_rahasia_ard001',
            'is_active' => true,
        ]);
    }
}
