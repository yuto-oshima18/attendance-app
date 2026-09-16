<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\CalculateWorkMinutesService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AttendanceReportController extends Controller
{
    /**
     * マイ勤怠レポート画面（一般ユーザー）を表示する。
     * 認証ユーザーの過去6ヶ月分の各種勤怠情報をviewに渡す。
     *
     * CalculateWorkMinutesService  実労働時間を分単位で計算する。
     */
    public function report(): View
    {
        $user = Auth::user();

        $today = Carbon::today();

        // 過去6ヶ月（当月を含む）
        $startDate = $today->copy()->subMonths(5)->startOfMonth();
        $endDate = $today->copy()->endOfMonth();

        /*
         * breakTimesをEager LoadingしてN+1を防止
         */
        $attendanceRecords = AttendanceRecord::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->orderBy('date')
            ->get();

        /*
         * 各勤怠の実労働時間・残業時間を算出
         */
        $attendanceRecords = $attendanceRecords->map(function ($record) {
            $workMinutes = CalculateWorkMinutesService::calculateWorkMinutes($record);

            return [
                'record' => $record,
                'date' => Carbon::parse($record->date),
                'work_minutes' => $workMinutes,
                'overtime_minutes' => max(0, $workMinutes - (8 * 60)),
            ];
        });

        /*
         * 基本サマリー
         */
        $totalWorkMinutes = $attendanceRecords->sum('work_minutes');

        $totalOvertimeMinutes = $attendanceRecords->sum('overtime_minutes');

        $avgWorkMinutes = $attendanceRecords->isNotEmpty()
            ? (int) round(
                $totalWorkMinutes / $attendanceRecords->count()
            )
            : 0;

        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $avgWorkMinutes,
        ];

        /*
         * 月次推移
         */
        $monthlyTrend = collect(range(5, 0))
            ->map(function ($monthOffset) use ($today, $attendanceRecords) {
                $month = $today->copy()->subMonths($monthOffset);

                $monthRecords = $attendanceRecords->filter(
                    fn ($item) => $item['date']->isSameMonth($month)
                );

                return [
                    'month' => $month->format('Y/m'),
                    'work_minutes' => $monthRecords->sum('work_minutes'),
                    'overtime_minutes' => $monthRecords->sum('overtime_minutes'),
                ];
            });

        /*
         * 今月の異常検知
         */
        $currentMonthRecords = $attendanceRecords->filter(
            fn ($item) => $item['date']->isSameMonth($today)
        );

        $anomalies = [
            // 始業09:00を超えた場合
            'late_count' => $currentMonthRecords
                ->filter(
                    fn ($item) => $item['record']->clock_in > '09:00:00'
                )
                ->count(),

            // 終業18:00より前の場合
            'early_leave_count' => $currentMonthRecords
                ->filter(
                    fn ($item) => $item['record']->clock_out < '18:00:00'
                )
                ->count(),

            // 実労働時間が10時間を超えた場合
            'long_work_count' => $currentMonthRecords
                ->filter(
                    fn ($item) => $item['work_minutes'] > (10 * 60)
                )
                ->count(),
        ];

        return view('reports.index', compact('summary', 'monthlyTrend', 'anomalies'));
    }
}
