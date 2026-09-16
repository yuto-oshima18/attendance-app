<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ApplicationRequestController extends Controller
{
    /**
     * 申請一覧画面（一般ユーザー）を表示する。
     * 認証済みユーザ−の申請したすべての申請を表示する。
     * 勤怠情報の日付を追加した申請情報とユーザー情報をviewに渡す。
     * 管理者であれば、管理者用のviewを表示する。
     *
     * @redirect View
     */
    public function index(): View
    {
        $user = Auth::user();

        $formattedApplications = $user->attendanceRecords()
            ->with('applications')
            ->get()
            ->flatMap(function ($attendanceRecord) {
                return $attendanceRecord->applications->map(function ($application) use ($attendanceRecord) {
                    $application->id = $application->attendance_record_id;
                    $application->date = $attendanceRecord->date;

                    return $application;
                });
            });

        return view('user.user-application-list', compact('user', 'formattedApplications'));
    }

    /**
     * 勤怠修正申請を実施する。
     * 引数にて受け取った勤怠情報を申請としてDBに登録する。
     * 登録後、申請一覧にリダイレクトする。
     *
     * @param  StoreApplicationRequest  $request  変更する勤怠情報
     * @param  string  $id  勤怠情報のID
     *
     * @redirect RedirectResponse
     */
    public function store(StoreApplicationRequest $request, string $id): RedirectResponse
    {

        $validated = $request->validated();

        $user = Auth::user();
        $attendanceRecord = $user->attendanceRecords()->findOrFail($id);

        // Applicationレコード作成
        $applicationRecord = $attendanceRecord->applications()->create([
            'new_clock_in' => $validated['new_clock_in'],
            'new_clock_out' => $validated['new_clock_out'] ?? null,
            'comment' => $validated['comment'],
            'approval_status' => '承認待ち',
            'application_date' => now()->format('Y-m-d'),
        ]);

        // proposalBreaksレコード作成
        if (! empty($validated['new_break_in'])) {
            foreach ($validated['new_break_in'] as $index => $newBreakIn) {
                $newBreakOut = $validated['new_break_out'][$index] ?? null;
                // 開始・終了の両方が空なら登録しない
                if ($newBreakIn === null && $newBreakOut === null) {
                    continue;
                }

                $applicationRecord->proposalBreaks()->create([
                    'new_break_in' => $newBreakIn,
                    'new_break_out' => $newBreakOut,
                ]);
            }
        }

        return redirect(route('stamp.correction.request.index'));
    }
}
