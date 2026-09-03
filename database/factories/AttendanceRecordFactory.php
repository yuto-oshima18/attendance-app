<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clockIn = fake()->dateTimeBetween('08:30', '09:30');

        $clockIn = Carbon::instance($clockIn);

        $clockOut = $clockIn->copy()
            ->addHours(fake()->numberBetween(8, 10))
            ->addMinutes(fake()->numberBetween(0, 30));

        return [
            'date' => fake()->date(),
            'clock_in' => $clockIn->format('H:i:s'),
            'clock_out' => $clockOut->format('H:i:s'),
        ];
    }
}
