<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Produk;
use App\Models\User;
use App\Models\Penimbangan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        DB::table('incoming_singkongs')->truncate();
        DB::table('incoming_rmpms')->truncate();
        if (Schema::hasTable('rmpm_items')) {
            DB::table('rmpm_items')->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ============================================================
        // 1. ADMIN
        // ============================================================
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'tipe' => 'fg',
        ]);

        // ============================================================
        // 2. OPERATORS — FG (Finished Goods) Pasuruan
        // ============================================================
        $fgOperators = [
            ['name' => 'Hamimah', 'email' => 'hamimah@test.com', 'shift' => 'Shift 1', 'start' => '4:00', 'end' => '12:00'],
            ['name' => 'Luknan', 'email' => 'luknan@test.com', 'shift' => 'Shift 2', 'start' => '12:00', 'end' => '16:00'],
            ['name' => 'Zahra', 'email' => 'zahra@test.com', 'shift' => 'Shift 3', 'start' => '16:00', 'end' => '20:00'],
            ['name' => 'Zahro', 'email' => 'zahro@test.com', 'shift' => 'Shift 2', 'start' => '12:00', 'end' => '16:00'],
        ];

        $fgUserModels = [];
        foreach ($fgOperators as $op) {
            $fgUserModels[] = User::create([
                'name' => $op['name'],
                'email' => $op['email'],
                'password' => Hash::make('password'),
                'role' => 'operator',
                'tipe' => 'fg',
                'shift' => $op['shift'],
                'shift_start' => $op['start'],
                'shift_end' => $op['end'],
                'shift_type' => 'normal',
            ]);
        }

        // ============================================================
        // 3. OPERATOR — Incoming Singkong Pasuruan
        // ============================================================
        User::create([
            'name' => 'Deby',
            'email' => 'deby@test.com',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'tipe' => 'incoming_singkong',
            'shift' => 'Long Shift',
            'shift_start' => '06:00',
            'shift_end' => '18:00',
            'shift_type' => 'long',
        ]);

        // ============================================================
        // 4. OPERATOR — Incoming RMPM Pasuruan
        // ============================================================
        User::create([
            'name' => 'Dwi Sinta',
            'email' => 'dwisinta@test.com',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'tipe' => 'incoming_rmpm',
            'shift' => 'Normal Shift',
            'shift_start' => '08:00',
            'shift_end' => '16:00',
            'shift_type' => 'normal',
        ]);

        // ============================================================
        // 5. OPERATOR — FG PSN
        // ============================================================
        User::create([
            'name' => 'Operator PSN',
            'email' => 'psn@test.com',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'tipe' => 'fg_psn',
            'shift' => 'Shift 1',
            'shift_start' => '06:00',
            'shift_end' => '14:00',
            'shift_type' => 'normal',
        ]);

        // ============================================================
        // 5B. OPERATOR — Formulasi Pasuruan
        // ============================================================
        User::create([
            'name' => 'Operator Formulasi',
            'email' => 'formulasi@test.com',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'tipe' => 'formulasi_pasuruan',
            'shift' => 'Shift 1',
            'shift_start' => '06:00',
            'shift_end' => '14:00',
            'shift_type' => 'normal',
        ]);

        // ============================================================
        // 6. OPERATOR — FG Surabaya (Formulasi Surabaya)
        // ============================================================
        User::create([
            'name' => 'Operator Surabaya',
            'email' => 'surabaya@test.com',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'tipe' => 'fg_surabaya',
            'shift' => 'Shift 1',
            'shift_start' => '06:00',
            'shift_end' => '14:00',
            'shift_type' => 'normal',
        ]);

        // ============================================================
        // 7. OPERATOR — CS Noodle Surabaya
        // ============================================================
        User::create([
            'name' => 'Operator CS Noodle',
            'email' => 'csnoodle@test.com',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'tipe' => 'cs_noodle_sby',
            'shift' => 'Shift 1',
            'shift_start' => '06:00',
            'shift_end' => '14:00',
            'shift_type' => 'normal',
        ]);

        // ============================================================
        // 8. OPERATOR — CS FG-Sby Surabaya
        // ============================================================
        User::create([
            'name' => 'Operator CS FG-Sby',
            'email' => 'csfgsby@test.com',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'tipe' => 'cs_fg_sby',
            'shift' => 'Shift 1',
            'shift_start' => '06:00',
            'shift_end' => '14:00',
            'shift_type' => 'normal',
        ]);

        // ============================================================
        // 9. PRODUKS (FG Products — with distinct target weights)
        // ============================================================
        $materials = [
            ['name' => 'Tepung LL', 'target' => 500],
            ['name' => 'Garam', 'target' => 50],
            ['name' => 'Gula Pasir', 'target' => 200],
            ['name' => 'Maizena', 'target' => 300],
            ['name' => 'Choco Flavor', 'target' => 100],
            ['name' => 'Baking Powder', 'target' => 25],
            ['name' => 'Vanila Bubuk', 'target' => 15],
            ['name' => 'Pati Sagu', 'target' => 1000],
            ['name' => 'Berry & Mix Seeds 80 Gr', 'target' => 80],
            ['name' => 'Berry & Mix Seeds 80 Gr Fresh', 'target' => 80],
            ['name' => 'Blackmond 12 Gr', 'target' => 12],
            ['name' => 'Blackmond 180 Gr', 'target' => 180],
            ['name' => 'Blackmond Mini Bites 80 Gr', 'target' => 80],
            ['name' => 'Blackthins 100 Gr', 'target' => 100],
            ['name' => 'Blackthins 12 Gr', 'target' => 12],
            ['name' => 'Blackthins 65 Gr', 'target' => 65],
            ['name' => 'Pasta (Fusilli) 100 Gr', 'target' => 100],
            ['name' => 'Pasta (Fusilli) 250 Gr', 'target' => 250],
            ['name' => 'Pasta (Mac and Cheese) 115 Gr', 'target' => 115],
            ['name' => 'Cheesethins 80 Gr Fresh', 'target' => 80],
            ['name' => 'Cheesethins 12 Gr', 'target' => 12],
            ['name' => 'Cheesethins 80 Gr', 'target' => 80],
            ['name' => 'Chocomond 180 Gr Fresh', 'target' => 180],
            ['name' => 'Chocothins 100 Gr Fresh', 'target' => 100],
            ['name' => 'Chocothins 65 Gr (Sea Salt)', 'target' => 65],
            ['name' => 'Matchathins 80 Gr', 'target' => 80],
            ['name' => 'Pumpberry 12 Gr', 'target' => 12],
            ['name' => 'Pumpberry 180 Gr', 'target' => 180],
            ['name' => 'Pumpberry Mini Bites 80 Gr', 'target' => 80],
            ['name' => 'Blackmond 33gr x 6 (198gr)', 'target' => 198],
            ['name' => 'Blackmond 500gr', 'target' => 500],
            ['name' => 'Pumpberry 500gr', 'target' => 500],
        ];

        $produkModels = [];
        foreach ($materials as $m) {
            $produkModels[] = Produk::create([
                'nama_produk' => $m['name'],
                'target_berat' => $m['target'],
            ]);
        }

        // ============================================================
        // 6. DEVICES
        // ============================================================

        // FG Device
        $fgDevice = Device::create([
            'device_code' => 'DEV-PAS-001',
            'device_name' => 'Wemos D1 R2 - Finishing 1',
            'device_token' => 'FG-PASURUAN-001',
            'is_active' => true,
        ]);

        // Incoming Singkong Device
        Device::create([
            'device_code' => 'DEV-PAS-INC-001',
            'device_name' => 'Wemos D1 R2 - Incoming Singkong',
            'device_token' => 'INC-SINGKONG-001',
            'is_active' => true,
        ]);

        // Incoming RMPM Device
        Device::create([
            'device_code' => 'DEV-PAS-INC-002',
            'device_name' => 'Wemos D1 R2 - Incoming RMPM',
            'device_token' => 'INC-RMPM-001',
            'is_active' => true,
        ]);

        // FG PSN Device
        Device::create([
            'device_code' => 'DEV-PSN-001',
            'device_name' => 'Wemos D1 R2 - FG PSN',
            'device_token' => 'FG-PSN-001',
            'is_active' => true,
        ]);

        // FG Surabaya Device
        Device::create([
            'device_code' => 'DEV-SBY-001',
            'device_name' => 'Wemos D1 R2 - FG Surabaya',
            'device_token' => 'FG-SBY-001',
            'is_active' => true,
        ]);

        // Formulasi Pasuruan Device
        Device::create([
            'device_code' => 'DEV-FORM-001',
            'device_name' => 'Wemos D1 R2 - Formulasi Pasuruan',
            'device_token' => 'FORM-PASURUAN-001',
            'is_active' => true,
        ]);

        // ============================================================
        // 7. PENIMBANGAN HISTORY (FG Mock data)
        // ============================================================
        foreach ($fgUserModels as $user) {
            for ($i = 1; $i <= 3; $i++) {
                $produk = $produkModels[array_rand($produkModels)];
                $target = $produk->target_berat;
                $berat = $target + (rand(-5, 5) / 100);
                $selisih = $berat - $target;

                Penimbangan::create([
                    'tanggal_penimbangan' => now()->subDays(rand(0, 2)),
                    'produk_id' => $produk->id,
                    'user_id' => $user->id,
                    'device_id' => $fgDevice->id,
                    'kode_produksi' => 'KP-' . now()->format('Ymd') . '-U' . $user->id . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'berat' => $berat,
                    'selisih' => $selisih,
                    'status' => 'selesai',
                    'created_at' => now()->subHours(rand(1, 24)),
                ]);
            }
        }

        $this->call([
            MasterDataSeeder::class,
        ]);
    }
}