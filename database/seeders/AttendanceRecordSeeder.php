<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        // 過去3ヶ月の平日を作成
        $dates = [];

        $startDate = now()->subMonths(3)->startOfDay();
        $endDate = now()->subDay()->startOfDay();

        $date = $startDate->copy();

        while ($date->lte($endDate)) {
            if ($date->isWeekday()) {
                $dates[] = $date->toDateString();
            }

            $date->addDay();
        }

        foreach ($users as $user) {
            foreach ($dates as $date) {
                // 10%程度は欠勤
                if (fake()->boolean(90)) {
                    $attendance = $user->attendanceRecords()->create(
                        AttendanceRecord::factory()->make([
                            'date' => $date,
                        ])->toArray()
                    );

                    // 休憩を1〜3件作成
                    $attendance->breakTimes()->createMany(
                        BreakTime::factory()
                            ->count(fake()->numberBetween(1, 3))
                            ->make()
                            ->toArray()
                    );
                }
            }
        }
    }
}
