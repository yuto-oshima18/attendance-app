<?php

namespace Database\Factories;

use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreakTime>
 */
class BreakTimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $in = Carbon::createFromTime(
            fake()->numberBetween(11, 13),
            fake()->numberBetween(0, 59)
        );

        $out = $in->copy()
            ->addMinutes(fake()->numberBetween(45, 75));

        return [
            'break_in' => $in->format('H:i:s'),
            'break_out' => $out->format('H:i:s'),
        ];
    }
}
