<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Admin user.
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'tipe' => 'fg',
        ]);
    }

    /**
     * Operator dengan shift yang mencakup waktu sekarang (00:00–23:59) dan
     * sesi tidak terkunci — sehingga ShiftService::getActiveOperator() memilihnya.
     */
    public function operator(string $tipe = 'fg'): static
    {
        return $this->state(fn () => [
            'role'           => 'operator',
            'tipe'           => $tipe,
            'shift'          => '1',
            'shift_start'    => '00:00:00',
            'shift_end'      => '23:59:59',
            'shift_type'     => 'normal',
            'session_locked' => false,
        ]);
    }
}
