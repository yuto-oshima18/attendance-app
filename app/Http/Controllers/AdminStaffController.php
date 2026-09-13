<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
