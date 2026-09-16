<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧画面（管理者）を表示する。
     * 全ユーザーの一覧を表示する。
     * 全ユーザー情報をviewに渡す。
     *
     * @redirect View
     */
    public function index(): View
    {
        $users = User::all();

        return view('admin.staff-list', compact('users'));
    }

    /**
     * スタッフ別勤怠一覧画面（管理者）を表示する。
     * 対象ユーザーの勤怠一覧を月毎に表示する。
     * 対象年月と前月、翌月、対象勤怠情報をviewに渡す。
     *
     * @param  string  $id  対象ユーザーのID
     * @param  Request  $request  表示月
     */
    public function show(string $id, Request $request): View
    {
        $date = Carbon::parse($request->query('date', now()->format('Y-m')));

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $formattedDate = $date->copy()->format('Y-m');

        $user = User::findOrFail($id);
        $formattedAttendanceRecords = $user->attendanceRecords()
            ->whereYear('date', substr($formattedDate, 0, 4))
            ->whereMonth('date', substr($formattedDate, 5, 2))
            ->with('breakTimes')
            ->get();

        $formattedAttendanceRecords->each(function ($attendanceRecord) {
            // 休憩時間の合計を計算
            $totalBreakSeconds = $attendanceRecord->breakTimes->sum(function ($breakTime) {
                if (! $breakTime->break_in || ! $breakTime->break_out) {
                    return 0;
                }

                return strtotime($breakTime->break_out) - strtotime($breakTime->break_in);
            });

            // 休憩時間の合計を追加
            if (! empty($totalBreakSeconds)) {
                $attendanceRecord->total_break_time = gmdate('H:i', $totalBreakSeconds);
            }

            // 勤務時間
            $totalWorkSeconds = 0;

            if ($attendanceRecord->clock_in && $attendanceRecord->clock_out) {
                $totalWorkSeconds = strtotime($attendanceRecord->clock_out) - strtotime($attendanceRecord->clock_in);
            }

            $totalTimeSeconds = $totalWorkSeconds - $totalBreakSeconds;

            // 勤務時間の合計を追加
            if (! empty($totalTimeSeconds)) {
                $attendanceRecord->total_time = gmdate('H:i', $totalTimeSeconds);
            }
        });

        return view('admin.staff-attendance-list', compact('date', 'user', 'previousMonth', 'nextMonth', 'formattedAttendanceRecords'));
    }

    /**
     * 対象ユーザーの月毎の勤怠情報をCSVファイルにエクスポートする。
     *
     * @param  Request  $request  対象ユーザーのIDと対象月の情報
     * @return StreamedResponse csv用に整形した勤怠情報をCSV化して返却
     */
    public function export(Request $request): StreamedResponse
    {
        $date = Carbon::createFromFormat('Y-m', $request->year_month);
        $formattedDate = $date->format('Y-m');

        $user = User::findOrFail($request->user_id);
        $formattedAttendanceRecords = $user->attendanceRecords()
            ->whereYear('date', substr($formattedDate, 0, 4))
            ->whereMonth('date', substr($formattedDate, 5, 2))
            ->with('breakTimes')
            ->get();

        $formattedAttendanceRecords->each(function ($attendanceRecord) {
            // 休憩時間の合計を計算
            $totalBreakSeconds = $attendanceRecord->breakTimes->sum(function ($breakTime) {
                if (! $breakTime->break_in || ! $breakTime->break_out) {
                    return 0;
                }

                return strtotime($breakTime->break_out) - strtotime($breakTime->break_in);
            });

            // 休憩時間の合計を追加
            if (! empty($totalBreakSeconds)) {
                $attendanceRecord->total_break_time = gmdate('H:i', $totalBreakSeconds);
            }

            // 勤務時間
            $totalWorkSeconds = 0;

            if ($attendanceRecord->clock_in && $attendanceRecord->clock_out) {
                $totalWorkSeconds = strtotime($attendanceRecord->clock_out) - strtotime($attendanceRecord->clock_in);
            }

            $totalTimeSeconds = $totalWorkSeconds - $totalBreakSeconds;

            // 勤務時間の合計を追加
            if (! empty($totalTimeSeconds)) {
                $attendanceRecord->total_time = gmdate('H:i', $totalTimeSeconds);
            }
        });

        return response()->streamDownload(function () use ($formattedAttendanceRecords, $user, $formattedDate) {
            $handle = fopen('php://output', 'w');

            // BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // ユーザー名・対象月
            fputcsv($handle, [
                $user->name,
                Carbon::createFromFormat('Y-m', $formattedDate)->format('Y年m月'),
            ]);

            // ヘッダー
            fputcsv($handle, [
                '日付',
                '出勤',
                '退勤',
                '休憩',
                '合計',
            ]);

            // データ
            foreach ($formattedAttendanceRecords as $attendanceRecord) {
                fputcsv($handle, [
                    $attendanceRecord->date
                        ? Carbon::parse($attendanceRecord->date)->format('Y-m-d')
                        : '',
                    $attendanceRecord->clock_in ?? '',
                    $attendanceRecord->clock_out ?? '',
                    $attendanceRecord->total_break_time
                        ? Carbon::parse($attendanceRecord->total_break_time)->format('G:i')
                        : '',
                    $attendanceRecord->total_time
                        ? Carbon::parse($attendanceRecord->total_time)->format('G:i')
                        : '',
                ]);
            }

            fclose($handle);
        }, 'attendance.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
