<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Produk;
use App\Models\User;
use App\Models\Penimbangan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with robust testing data.
     */
    public function run(): void
    {
        // Clear existing data to prevent duplicates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Produk::truncate();
        Device::truncate();
        Penimbangan::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. ADMIN
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. OPERATORS (Assigned to shifts as per ShiftService)
        // Shift 1: 08:00 - 12:00
        // Shift 2: 12:00 - 16:00
        // Shift 3: 16:00 - 20:00
        $operators = [
            ['name' => 'Hamimah', 'email' => 'hamimah@test.com', 'shift' => 'Shift 1', 'start' => '4:00', 'end' => '12:00'],
            ['name' => 'Luknan', 'email' => 'luknan@test.com', 'shift' => 'Shift 2', 'start' => '12:00', 'end' => '16:00'],
            ['name' => 'Zahra', 'email' => 'zahra@test.com', 'shift' => 'Shift 3', 'start' => '16:00', 'end' => '20:00'],
            ['name' => 'Zahro', 'email' => 'zahro@test.com', 'shift' => 'Shift 2', 'start' => '12:00', 'end' => '16:00'],
        ];

        $userModels = [];
        foreach ($operators as $op) {
            $userModels[] = User::create([
                'name' => $op['name'],
                'email' => $op['email'],
                'password' => Hash::make('password'),
                'role' => 'operator',
                'shift' => $op['shift'],
                'shift_start' => $op['start'],
                'shift_end' => $op['end'],
            ]);
        }

        // 3. PRODUKS (With distinct target weights)
        $materials = [
            ['name' => 'Tepung LL', 'target' => 500],
            ['name' => 'Garam', 'target' => 50],
            ['name' => 'Gula Pasir', 'target' => 200],
            ['name' => 'Maizena', 'target' => 300],
            ['name' => 'Choco Flavor', 'target' => 100],
            ['name' => 'Baking Powder', 'target' => 25],
            ['name' => 'Vanila Bubuk', 'target' => 15],
            ['name' => 'Pati Sagu', 'target' => 1000],
        ];

        $produkModels = [];
        foreach ($materials as $m) {
            $produkModels[] = Produk::create([
                'nama_produk' => $m['name'],
                'target_berat' => $m['target'],
            ]);
        }

        // 4. DEVICE
        $device = Device::create([
            'device_code' => 'DEV-PAS-001',
            'device_name' => 'Wemos D1 R2 - Finishing 1',
            'device_token' => 'FG-PASURUAN-001',
            'is_active' => true,
            'current_product_id' => $produkModels[0]->id,
        ]);

        // 5. PENIMBANGAN HISTORY (Mock data for the last 2 days)
        foreach ($userModels as $user) {
            // Generate 3 completed records for each user
            for ($i = 1; $i <= 3; $i++) {
                $produk = $produkModels[array_rand($produkModels)];
                $target = $produk->target_berat;
                
                // Simulate slight variance in measurement
                $berat = $target + (rand(-5, 5) / 100); 
                $selisih = $berat - $target;

                Penimbangan::create([
                    'tanggal_penimbangan' => now()->subDays(rand(0, 2)),
                    'produk_id' => $produk->id,
                    'user_id' => $user->id,
                    'device_id' => $device->id,
                    'kode_produksi' => 'LOT-' . now()->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'berat' => $berat,
                    'selisih' => $selisih,
                    'status' => 'selesai',
                    'created_at' => now()->subHours(rand(1, 24)),
                ]);
            }

            // Generate 1 pending record for the "active" operator
            // This is useful for testing the simulator immediately
            if ($user->shift === '1' && now()->format('H') < 12) {
                 Penimbangan::create([
                    'tanggal_penimbangan' => now(),
                    'produk_id' => $produkModels[0]->id,
                    'user_id' => $user->id,
                    'kode_produksi' => 'TEST-ACTIVE-001',
                    'berat' => 0,
                    'selisih' => 0,
                    'status' => 'menunggu',
                ]);
            }
        }
    }
}
