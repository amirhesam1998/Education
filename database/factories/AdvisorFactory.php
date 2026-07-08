<?php

namespace Database\Factories;

use App\Models\Advisor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Advisor>
 */
class AdvisorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }
}
