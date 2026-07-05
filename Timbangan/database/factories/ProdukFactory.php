<?php

namespace Database\Factories;

use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produk>
 */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    public function definition(): array
    {
        return [
            'nama_produk'  => ucwords(fake()->unique()->words(2, true)),
            'target_berat' => fake()->randomFloat(2, 5, 50),
        ];
    }
}
