<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Carbon\Carbon;

class CalculateWorkMinutesService
{
    /**
     * 実労働時間を分単位で計算する。
     *
     * @param  AttendanceRecord  $record  実労働時間を計算する勤怠情報
     */
    public static function calculateWorkMinutes(AttendanceRecord $record): int
    {
        $clockIn = Carbon::parse(
            $record->date.' '.$record->clock_in
        );

        $clockOut = Carbon::parse(
            $record->date.' '.$record->clock_out
        );

        $workMinutes = $clockIn->diffInMinutes($clockOut);

        $breakMinutes = $record->breakTimes
            ->map(
                fn ($breakTime) => Carbon::parse(
                    $record->date.' '.$breakTime->break_in
                )->diffInMinutes(
                    Carbon::parse(
                        $record->date.' '.$breakTime->break_out
                    )
                )
            )
            ->sum();

        return $workMinutes - $breakMinutes;
    }
}
