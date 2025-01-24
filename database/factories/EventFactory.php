<?php

namespace Database\Factories;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startDate = Carbon::instance($this->faker->dateTimeBetween('-1 month', '+1 month'));
        $endDate = (clone $startDate)->addHour();

        return [
            'title' => $this->faker->sentence(3),
            'start_datetime' => $startDate->format('Y-m-d H:i:s'),
            'end_datetime' => $endDate->format('Y-m-d H:i:s'),
        ];
    }
} 