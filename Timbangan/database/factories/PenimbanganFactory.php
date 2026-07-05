<?php

namespace Database\Factories;

use App\Models\Penimbangan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penimbangan>
 */
class PenimbanganFactory extends Factory
{
    protected $model = Penimbangan::class;

    public function definition(): array
    {
        return [
            'tanggal_penimbangan' => now()->toDateString(),
            'produk_id'           => Produk::factory(),
            'user_id'             => User::factory(),
            'kode_produksi'       => 'LOT-'.fake()->unique()->numerify('######'),
            'tanggal_expired'     => now()->addYear()->toDateString(),
            'berat'               => 0,
            'selisih'             => 0,
            'status'              => 'menunggu',
        ];
    }

    public function selesai(float $berat = 10.0): static
    {
        return $this->state(fn () => ['status' => 'selesai', 'berat' => $berat]);
    }
}
