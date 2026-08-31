<?php

namespace Database\Factories;

use App\Enums\SlotStatus;
use App\Models\Advisor;
use App\Models\ReservationSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationSlot>
 */
class ReservationSlotFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+10 days')->format('Y-m-d').' 10:00';

        return [
            'advisor_id' => Advisor::factory(),
            'date' => substr($start, 0, 10),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 15,
            'capacity' => 1,
            'status' => SlotStatus::Active,
            'created_by' => User::factory(),
        ];
    }
}
