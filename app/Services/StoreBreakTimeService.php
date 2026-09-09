<?php

namespace App\Services;

use App\Models\User;

class StoreBreakTimeService
{
    /**
     * 休憩開始処理を実施する。
     * attendance_statusに「休憩中」を設定する。
     * break_timesテーブルにレコードを作成する。
     *
     * @param  User  $user  認証ユーザー情報
     * @param  int  $latestAttendanceId  休憩情報を追加するattendance_recordsのレコードID
     */
    public static function storeBreakTime(User $user, int $latestAttendanceId): void
    {
        $user->update([
            'attendance_status' => '休憩中',
        ]);

        $formattedTime = now()->format('H:i:s');

        $attendanceRecord = $user->attendanceRecords()->findOrFail($latestAttendanceId);
        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTime,
        ]);
    }
}
