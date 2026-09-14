<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReportUserAttendanceRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User1の勤怠情報を作成(レポート確認用)
        $reportUser = User::findOrFail(1);

        $today = Carbon::today();

        /*
         * ========================================
         * 過去5ヶ月
         * 各月 平日15日
         * 通常勤務 09:00 - 18:00
         * ========================================
         */
        for ($monthOffset = 5; $monthOffset >= 1; $monthOffset--) {
            $month = $today->copy()->subMonths($monthOffset);

            $weekdays = $this->getWeekdaysInMonth($month);

            // 各月の最初の平日15日
            $dates = array_slice($weekdays, 0, 15);

            foreach ($dates as $date) {
                $this->createAttendance(
                    $reportUser,
                    $date,
                    '09:00:00',
                    '18:00:00'
                );
            }
        }

        /*
         * ========================================
         * 当月17日
         *
         * 通常       10日
         * 残業        3日
         * 遅刻        2日
         * 早退        1日
         * 長時間労働  1日
         * ========================================
         */
        $currentMonthWeekdays = $this->getWeekdaysInMonth($today);

        // 当月の最初の17平日
        $dates = array_slice($currentMonthWeekdays, 0, 17);

        $patterns = [
            // 通常 10日
            ...array_fill(0, 10, [
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]),

            // 残業 3日
            ...array_fill(0, 3, [
                'clock_in' => '09:00:00',
                'clock_out' => '20:00:00',
            ]),

            // 遅刻 2日
            ...array_fill(0, 2, [
                'clock_in' => '09:30:00',
                'clock_out' => '18:00:00',
            ]),

            // 早退 1日
            [
                'clock_in' => '09:00:00',
                'clock_out' => '17:00:00',
            ],

            // 長時間労働 1日
            [
                'clock_in' => '08:00:00',
                'clock_out' => '21:00:00',
            ],
        ];

        foreach ($dates as $index => $date) {
            $pattern = $patterns[$index];

            $this->createAttendance(
                $reportUser,
                $date,
                $pattern['clock_in'],
                $pattern['clock_out']
            );
        }
    }

    /**
     * 指定月の平日を取得
     *
     * @return array<int, string>
     */
    private function getWeekdaysInMonth(Carbon $month): array
    {
        $dates = [];

        $current = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $dates[] = $current->format('Y-m-d');
            }

            $current->addDay();
        }

        return $dates;
    }

    /**
     * 勤怠と固定休憩を作成
     */
    private function createAttendance(
        User $reportUser,
        string $date,
        string $clockIn,
        string $clockOut
    ): void {
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $reportUser->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => null,
        ]);

        // 固定休憩 12:00 - 13:00
        BreakTime::create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }
}
