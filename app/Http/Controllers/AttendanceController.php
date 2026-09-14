<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\CalculateWorkMinutesService;
use App\Services\StoreAttendanceService;
use App\Services\StoreBreakTimeService;
use App\Services\UpdateAttendanceService;
use App\Services\UpdateBreakTimeService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面（一般ユーザー）を表示する。
     * 認証ユーザーの勤怠一覧を月毎に表示する。
     * 対象年月と前月、翌月、対象勤怠情報をviewに渡す。
     */
    public function index(Request $request): View
    {
        $date = Carbon::parse($request->query('date', now()->format('Y-m')));

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $formattedDate = $date->copy()->format('Y-m');

        $user = Auth::user();
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

        return view('user.user-attendance-list', compact('date', 'previousMonth', 'nextMonth', 'formattedAttendanceRecords'));
    }

    /**
     * 出勤登録画面（一般ユーザー）を表示する。
     * 認証ユーザーのattendance_statusを確認し、「退勤済」かつ最終打刻が現在の日付より前の場合、
     * attendance_status「勤務外」に設定する。
     * 現在の日付と時間、認証ユーザー情報をviewに渡す。
     */
    public function create(): View
    {
        $user = Auth::user();

        $formattedDate = now()->format('Y-m-d');
        $formattedTime = now()->format('H:i:s');

        // attendance_statusが「退勤済」のユーザーは、最終打刻が前日である場合は「勤務外」に設定する
        $latestAttendance = $user->attendanceRecords()->latest('date')->first();
        if (($user->attendance_status === '退勤済') && ($latestAttendance->date < $formattedDate)) {
            $user->update([
                'attendance_status' => '勤務外',
            ]);
        }

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 打刻処理を実施する。
     * 引数にて実行するアクションを受け取り、アクションごとにサービスクラスを実行。
     * StoreAttendanceService  clock_inへ打刻
     * UpdateAttendanceService  clock_outへ打刻
     * StoreBreakTimeService  break_inへ打刻
     * UpdateBreakTimeService  break_outへ打刻
     *
     * redirectでcreate()メソッドを実行する。
     *
     * @param  Request  $request  実行するアクション
     */
    public function store(Request $request): RedirectResponse
    {
        $action = $request->action;

        $user = Auth::user();
        $latestAttendance = $user->attendanceRecords()->latest('date')->first();
        if (! empty($latestAttendance)) {
            $latestAttendanceId = $latestAttendance->id;
        }

        switch ($action) {
            case 'clock_in':
                StoreAttendanceService::storeAttendance($user);
                break;
            case 'clock_out':
                UpdateAttendanceService::updateAttendanceClockOut($user, $latestAttendanceId);
                break;
            case 'break_in':
                StoreBreakTimeService::storeBreakTime($user, $latestAttendanceId);
                break;
            case 'break_out':
                UpdateBreakTimeService::updateBreakTime($user, $latestAttendanceId);
                break;
            default:
                abort(400);
                break;
        }

        return redirect(route('attendance.create'));
    }

    /**
     * 勤怠詳細画面（一般ユーザー）を表示する。
     * 認証ユーザーの勤怠の詳細を表示する。
     * 認証ユーザ情報と対象勤怠詳細をviewに渡す。
     *
     * @param  string  $id  表示する勤怠情報のid
     */
    public function show(string $id): View
    {
        $user = Auth::user();
        $data = $user->attendanceRecords()
            ->with('breakTimes', 'applications')
            ->findOrFail($id);

        $date = Carbon::parse($data->date);
        $data->year = $date->year;
        $data->date = $date->format('m-d');

        $data->breaks = $data->breakTimes;

        // $applicationRecord = $data->applications()->latest('application_date')->first();

        $applicationRecord = $data->applications()
            ->where('approval_status', '承認待ち')
            ->latest('application_date')
            ->first();

        $data->application = $applicationRecord;

        return view('user.user-detail', compact('user', 'data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

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
