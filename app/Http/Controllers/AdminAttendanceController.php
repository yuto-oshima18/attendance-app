<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAttendanceRecordRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    /**
     * 勤怠一覧画面（管理者）を表示する。
     * その日になされた全ユーザーの勤怠情報を表示する。
     * 対象年月日とその前日、翌日、全ユーザー情報と対象日の勤怠情報をviewに渡す。
     *
     * @param  Request  $request  クエリパラメータが存在する場合、対象年月日の情報
     *
     * @redirect View
     */
    public function index(Request $request): View
    {
        $date = Carbon::parse($request->query('date', now()->format('Y-m-d')));

        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        $formattedDate = $date->copy()->format('Y-m-d');

        $users = User::all();

        $attendanceRecords = AttendanceRecord::whereDate('date', $date)->with('breakTimes')->get();

        $attendanceRecords->each(function ($attendanceRecord) {
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

        return view('admin.admin-attendance-list', compact('date', 'previousDay', 'nextDay', 'users', 'attendanceRecords'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * 勤怠詳細画面（管理者）を表示する。
     * 勤怠の詳細を表示する。
     * 対象のユーザ情報と対象勤怠詳細をviewに渡す。
     *
     * @param  string  $id  表示する勤怠情報のid
     *
     * @teturn View
     */
    public function show(string $id)
    {
        $attendanceRecord = AttendanceRecord::with('user', 'breakTimes', 'applications')->findOrFail($id);
        $user = $attendanceRecord->user;

        $date = Carbon::parse($attendanceRecord->date);
        $attendanceRecord->year = $date->year;
        $attendanceRecord->date = $date->format('m-d');

        $attendanceRecord->breaks = $attendanceRecord->breakTimes->toArray();

        $applicationRecord = $attendanceRecord->applications()
            ->where('approval_status', '承認待ち')
            ->latest('application_date')
            ->first();

        $attendanceRecord->application = $applicationRecord;

        return view('admin.admin-detail', compact('user', 'attendanceRecord'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * 勤怠詳細画面（管理者）
     * 対象の勤怠情報を更新する。
     * 既存の休憩レコードは削除し、新規情報で再作成。
     * リダイレクトでindexメソッドを実行する。
     *
     * @param  UpdateAttendanceRecordRequest  $request  変更する情報
     * @param  string  $id  変更対象の勤怠情報ID
     *
     * @redirect RedirectResponse
     */
    public function update(UpdateAttendanceRecordRequest $request, string $id): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $id) {
            $attendanceRecord = AttendanceRecord::with('breakTimes')->findOrFail($id);

            // フォーム情報の出勤退勤時間と備考で更新
            $attendanceRecord->update([
                'clock_in' => $validated['new_clock_in'],
                'clock_out' => $validated['new_clock_out'],
                'comment' => $validated['comment'],
            ]);

            // 登録されている休憩時間を全て削除
            $attendanceRecord->breakTimes()->delete();

            // フォーム情報の休憩時間で再登録
            foreach ($validated['new_break_in'] as $index => $breakIn) {
                $breakOut = $validated['new_break_out'][$index] ?? null;

                if ($breakIn === null || $breakOut === null) {
                    continue;
                }

                $attendanceRecord->breakTimes()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }
        });

        return redirect(route('admin.attendance.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
