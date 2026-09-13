<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class AdminApplicationRequestController extends Controller
{
    /**
     * 申請一覧画面（管理者）
     * 全ユーザ−のすべての申請を表示する。
     * 全ユーザの申請情報をビューに渡す。
     */
    public function index(): View
    {
        $applications = Application::with('attendanceRecord.user')->get();

        $applications->each(function ($application) {
            $application->user = $application->attendanceRecord->user;
        });

        return view('admin.admin-application-list', compact('applications'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function store(Request $request, string $id) {}

    /**
     * 修正申請承認画面（管理者）
     * 対象の申請の詳細を表示する。
     * 対象の申請情報をビューに渡す。
     *
     * @param  string  $id  対象申請のID
     */
    public function show(string $id): View
    {
        $application = Application::with('attendanceRecord.user', 'proposalBreaks')->findOrFail($id);
        $application->new_date = Carbon::parse($application->attendanceRecord->date);
        $application->proposalBreaks->each(function ($proposalBreak) {
            $proposalBreak->break_in = $proposalBreak->new_break_in;
            $proposalBreak->break_out = $proposalBreak->new_break_out;
        });

        $user = $application->attendanceRecord->user;

        return view('admin.admin-application-detail', compact('user', 'application'));
    }

    public function edit(string $id) {}

    /**
     * 修正申請承認画面（管理者）
     * 対象の申請にて勤怠情報を更新する。
     * 既存の休憩レコードは削除し、申請の休憩情報で再作成。
     * リダイレクトでindexメソッドを実行する。
     *
     * @param  Request  $request  申請の情報(viewから渡される情報が足りないため未使用)
     * @param  string  $id  変更を承認された申請のID
     *
     * @redirect RedirectResponse
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        DB::transaction(function () use ($id) {
            $application = Application::with('attendanceRecord', 'proposalBreaks')->findOrFail($id);

            // 申請内容の出勤退勤時間と備考で更新
            $application->attendanceRecord->update([
                'clock_in' => $application->new_clock_in,
                'clock_out' => $application->new_clock_out,
                'comment' => $application->comment,
            ]);

            // 登録されている休憩時間を全て削除
            $application->attendanceRecord->breakTimes()->delete();

            // 申請内容の休憩時間で再登録
            foreach ($application->proposalBreaks as $proposalBreak) {
                $application->attendanceRecord->breakTimes()->create([
                    'break_in' => $proposalBreak->new_break_in,
                    'break_out' => $proposalBreak->new_break_out,
                ]);
            }

            // 申請を承認済みにする
            $application->update([
                'approval_status' => '承認済み',
            ]);

        });

        return redirect(route('stamp.correction.request.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
