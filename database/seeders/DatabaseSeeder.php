<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Produk;
use App\Models\Device;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Users
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'tipe' => 'fg',
            ]
        );
        
        User::updateOrCreate(
            ['email' => 'operator@gmail.com'],
            [
                'name' => 'Operator',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'tipe' => 'fg',
            ]
        );

        // Products
        $products = [
            ['nama_produk' => 'Tepung Singkong 1kg', 'target_berat' => 1.000],
            ['nama_produk' => 'Tepung Singkong 5kg', 'target_berat' => 5.000],
            ['nama_produk' => 'Mocaf 1kg', 'target_berat' => 1.000],
            ['nama_produk' => 'Mocaf 25kg', 'target_berat' => 25.000],
        ];

        foreach ($products as $product) {
            Produk::updateOrCreate(['nama_produk' => $product['nama_produk']], $product);
        }

        // Devices
        Device::updateOrCreate(
            ['device_code' => 'DEV001'],
            [
                'device_name' => 'Timbangan Surabaya 1',
                'device_token' => 'token123',
                'is_active' => true,
            ]
        );
    }
}