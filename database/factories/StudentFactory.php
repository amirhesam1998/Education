<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'major' => fake()->randomElement(['ریاضی', 'تجربی', 'انسانی']),
            'score' => (string) fake()->numberBetween(5000, 12000),
            'exam_type' => fake()->randomElement(['سراسری', 'آزاد', 'تجربی']),
        ];
    }
}
