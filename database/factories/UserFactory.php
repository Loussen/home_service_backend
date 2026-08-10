<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'phone' => '+994'.fake()->unique()->numerify('50#######'),
            'name' => fake()->name(),
            'active_role' => 'client',
            'balance' => 0,
            'status' => 'active',
            'phone_verified_at' => now(),
        ];
    }
}
