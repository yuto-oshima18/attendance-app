<?php

namespace App\Services;

use App\Models\User;

class StoreAttendanceService
{
    /**
     * 出勤処理を実施する。
     * attendance_statusに「出勤中」を設定する。
     * attendance_recordsテーブルにレコードを作成する。
     *
     * @param  User  $user  認証ユーザー情報
     */
    public static function storeAttendance(User $user): void
    {
        $user->update([
            'attendance_status' => '出勤中',
        ]);

        $formattedDate = now()->format('Y-m-d');
        $formattedTime = now()->format('H:i:s');

        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);
    }
}
