<?php

namespace App\Services;

use App\Models\User;

class UpdateAttendanceService
{
    /**
     * 退勤処理を実施する。
     * attendance_statusに「退勤済」を設定する。
     * attendance_recordsテーブルのレコードを編集する。
     *
     * @param  User  $user  認証ユーザー情報
     * @param  int  $latestAttendanceId  編集するattendance_recordsのレコードID
     */
    public static function updateAttendanceClockOut(User $user, int $latestAttendanceId): void
    {
        $user->update([
            'attendance_status' => '退勤済',
        ]);

        $formattedTime = now()->format('H:i:s');

        $user->attendanceRecords()->findOrFail($latestAttendanceId)->update([
            'clock_out' => $formattedTime,
        ]);
    }
}
