<?php

namespace App\Http\Controllers;

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
        $latestAttendanceId = $latestAttendance->id;

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

        $applicationRecord = $data->applications()->latest('application_date')->first();
        if (! empty($applicationRecord)) {
            if ($applicationRecord->approval_status === '承認待ち') {
                $data->application = $applicationRecord;
            }
        }

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
}
