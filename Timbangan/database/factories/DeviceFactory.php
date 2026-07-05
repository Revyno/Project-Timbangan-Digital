<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'device_code'  => 'DEV-'.fake()->unique()->numerify('#####'),
            'device_name'  => 'Timbangan '.fake()->word(),
            'device_token' => fake()->unique()->sha1(),
            'is_active'    => true,
        ];
    }
}
