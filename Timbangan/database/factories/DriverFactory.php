<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'name'        => fake()->name(),
            'supplier_id' => Supplier::factory(),
            'nomor_plat'  => strtoupper(fake()->bothify('? #### ??')),
            'qr_code'     => 'DRV-'.fake()->unique()->numerify('######'),
            'asal'        => fake()->city(),
        ];
    }
}
