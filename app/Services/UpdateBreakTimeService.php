<?php

namespace App\Services;

use App\Models\User;

class UpdateBreakTimeService
{
    /**
     * 休憩終了処理を実施する。
     * attendance_statusに「出勤中」を設定する。
     * break_timesテーブルのレコードを編集する。
     *
     * @param  User  $user  認証ユーザー情報
     * @param  int  $latestAttendanceId  編集する休憩情報をに紐づくattendance_recordsのレコードID
     */
    public static function updateBreakTime(User $user, int $latestAttendanceId): void
    {
        $user->update([
            'attendance_status' => '出勤中',
        ]);

        $formattedTime = now()->format('H:i:s');

        $attendanceRecord = $user->attendanceRecords()->findOrFail($latestAttendanceId);
        $attendanceRecord->breakTimes()->latest('id')->first()->update([
            'break_out' => $formattedTime,
        ]);
    }
}
