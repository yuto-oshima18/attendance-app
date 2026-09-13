<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 項目：勤怠情報修正機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 修正申請一覧ページを開き、承認待ちのタブを開く
     */
    public function 承認待ちの修正申請が全て表示されている(): void
    {
        $generalUser = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        $adminUser = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 一般ユーザーの勤怠情報登録
        $generalFormattedDate = now()->subDay()->format('Y-m-d');
        $generalFormattedTimeClockIn = now()->subDay()->format('H:i:s');
        $generalFormattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $generalFormattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $generalFormattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $generalAttendanceRecordId = 1;
        $generalAttendanceRecord = $generalUser->attendanceRecords()->create([
            'id' => $generalAttendanceRecordId,
            'date' => $generalFormattedDate,
            'clock_in' => $generalFormattedTimeClockIn,
            'clock_out' => $generalFormattedTimeClockOut,
        ]);

        $generalAttendanceRecord->breakTimes()->create([
            'break_in' => $generalFormattedTimeBreakIn,
            'break_out' => $generalFormattedTimeBreakOut,
        ]);

        $generalApplicationData = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'general',
        ];

        // 一般ユーザー申請登録
        $response = $this->actingAs($generalUser)->post(route('app.attendance.show', $generalAttendanceRecordId), $generalApplicationData);

        // 管理者ユーザーの勤怠情報登録
        $adminFormattedDate = now()->subDays(2)->format('Y-m-d');
        $adminFormattedTimeClockIn = now()->subDays(2)->format('H:i:s');
        $adminFormattedTimeClockOut = now()->subDays(2)->addHours(9)->format('H:i:s');
        $adminFormattedTimeBreakIn = now()->subDays(2)->addHours(3)->format('H:i:s');
        $adminFormattedTimeBreakOut = now()->subDays(2)->addHours(4)->format('H:i:s');
        $adminAttendanceRecordId = 2;
        $adminAttendanceRecord = $adminUser->attendanceRecords()->create([
            'id' => $adminAttendanceRecordId,
            'date' => $adminFormattedDate,
            'clock_in' => $adminFormattedTimeClockIn,
            'clock_out' => $adminFormattedTimeClockOut,
        ]);

        $adminAttendanceRecord->breakTimes()->create([
            'break_in' => $adminFormattedTimeBreakIn,
            'break_out' => $adminFormattedTimeBreakOut,
        ]);

        $adminApplicationData = [
            'new_clock_in' => '10:00:00',
            'new_clock_out' => '19:00:00',
            'new_break_in' => ['13:00:00', '14:00:00'],
            'new_break_out' => ['13:30:00', '14:30:00'],
            'comment' => 'admin',
        ];

        // 管理者の申請登録
        $response = $this->actingAs($adminUser)->post(route('app.attendance.show', $adminAttendanceRecordId), $adminApplicationData);

        // 申請一覧(管理者)確認
        $response = $this->actingAs($adminUser)->get(route('stamp.correction.request.index'));

        $response->assertStatus(200);

        $response->assertSee('承認待ち');
        $response->assertSee($generalUser->name);
        $response->assertSee(now()->subDay()->format('Y/m/d'));
        $response->assertSee($generalApplicationData['comment']);
        $response->assertSee(now()->format('Y/m/d'));

        $response->assertSee('承認待ち');
        $response->assertSee($adminUser->name);
        $response->assertSee(now()->subDays(2)->format('Y/m/d'));
        $response->assertSee($adminApplicationData['comment']);
        $response->assertSee(now()->format('Y/m/d'));
    }

    /**
     * @test
     * 項目：勤怠情報修正機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 修正申請一覧ページを開き、承認済みのタブを開く
     */
    public function 承認済みの修正申請が全て表示されている(): void
    {
        $generalUser = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        $adminUser = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 一般ユーザーの勤怠情報登録
        $generalFormattedDate = now()->subDay()->format('Y-m-d');
        $generalFormattedTimeClockIn = now()->subDay()->format('H:i:s');
        $generalFormattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $generalFormattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $generalFormattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $generalAttendanceRecordId = 1;
        $generalAttendanceRecord = $generalUser->attendanceRecords()->create([
            'id' => $generalAttendanceRecordId,
            'date' => $generalFormattedDate,
            'clock_in' => $generalFormattedTimeClockIn,
            'clock_out' => $generalFormattedTimeClockOut,
        ]);

        $generalAttendanceRecord->breakTimes()->create([
            'break_in' => $generalFormattedTimeBreakIn,
            'break_out' => $generalFormattedTimeBreakOut,
        ]);

        $generalApplicationRecordId = 1;
        $generalApplicationData = [
            'id' => $generalApplicationRecordId,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'general',
        ];

        // 一般ユーザー申請登録
        $response = $this->actingAs($generalUser)->post(route('app.attendance.show', $generalAttendanceRecordId), $generalApplicationData);

        Application::findOrFail($generalApplicationRecordId)->update([
            'approval_status' => '承認済み',
        ]);

        // 管理者ユーザーの勤怠情報登録
        $adminFormattedDate = now()->subDays(2)->format('Y-m-d');
        $adminFormattedTimeClockIn = now()->subDays(2)->format('H:i:s');
        $adminFormattedTimeClockOut = now()->subDays(2)->addHours(9)->format('H:i:s');
        $adminFormattedTimeBreakIn = now()->subDays(2)->addHours(3)->format('H:i:s');
        $adminFormattedTimeBreakOut = now()->subDays(2)->addHours(4)->format('H:i:s');
        $adminAttendanceRecordId = 2;
        $adminAttendanceRecord = $adminUser->attendanceRecords()->create([
            'id' => $adminAttendanceRecordId,
            'date' => $adminFormattedDate,
            'clock_in' => $adminFormattedTimeClockIn,
            'clock_out' => $adminFormattedTimeClockOut,
        ]);

        $adminAttendanceRecord->breakTimes()->create([
            'break_in' => $adminFormattedTimeBreakIn,
            'break_out' => $adminFormattedTimeBreakOut,
        ]);

        $adminApplicationRecordId = 2;
        $adminApplicationData = [
            'id' => $adminApplicationRecordId,
            'new_clock_in' => '10:00:00',
            'new_clock_out' => '19:00:00',
            'new_break_in' => ['13:00:00', '14:00:00'],
            'new_break_out' => ['13:30:00', '14:30:00'],
            'comment' => 'admin',
        ];

        // 管理者の申請登録
        $response = $this->actingAs($adminUser)->post(route('app.attendance.show', $adminAttendanceRecordId), $adminApplicationData);

        Application::findOrFail($adminApplicationRecordId)->update([
            'approval_status' => '承認済み',
        ]);

        // 申請一覧(管理者)確認
        $response = $this->actingAs($adminUser)->get(route('stamp.correction.request.index'));

        $response->assertStatus(200);

        $response->assertSee('承認済み');
        $response->assertSee($generalUser->name);
        $response->assertSee(now()->subDay()->format('Y/m/d'));
        $response->assertSee($generalApplicationData['comment']);
        $response->assertSee(now()->format('Y/m/d'));

        $response->assertSee('承認済み');
        $response->assertSee($adminUser->name);
        $response->assertSee(now()->subDays(2)->format('Y/m/d'));
        $response->assertSee($adminApplicationData['comment']);
        $response->assertSee(now()->format('Y/m/d'));
    }

    /**
     * @test
     * 項目：勤怠情報修正機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 修正申請の詳細画面を開く
     */
    public function 修正申請の詳細内容が正しく表示されている(): void
    {
        $generalUser = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        $adminUser = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 一般ユーザーの勤怠情報登録
        $generalFormattedDate = now()->subDay()->format('Y-m-d');
        $generalFormattedTimeClockIn = now()->subDay()->format('H:i:s');
        $generalFormattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $generalFormattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $generalFormattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $generalAttendanceRecordId = 1;
        $generalAttendanceRecord = $generalUser->attendanceRecords()->create([
            'id' => $generalAttendanceRecordId,
            'date' => $generalFormattedDate,
            'clock_in' => $generalFormattedTimeClockIn,
            'clock_out' => $generalFormattedTimeClockOut,
        ]);

        $generalAttendanceRecord->breakTimes()->create([
            'break_in' => $generalFormattedTimeBreakIn,
            'break_out' => $generalFormattedTimeBreakOut,
        ]);

        $generalApplicationRecordId = 1;
        $generalApplicationData = [
            'id' => $generalApplicationRecordId,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'general',
        ];

        // 一般ユーザー申請登録
        $response = $this->actingAs($generalUser)->post(route('app.attendance.show', $generalAttendanceRecordId), $generalApplicationData);

        // 管理者ユーザーの勤怠情報登録
        $adminFormattedDate = now()->subDays(2)->format('Y-m-d');
        $adminFormattedTimeClockIn = now()->subDays(2)->format('H:i:s');
        $adminFormattedTimeClockOut = now()->subDays(2)->addHours(9)->format('H:i:s');
        $adminFormattedTimeBreakIn = now()->subDays(2)->addHours(3)->format('H:i:s');
        $adminFormattedTimeBreakOut = now()->subDays(2)->addHours(4)->format('H:i:s');
        $adminAttendanceRecordId = 2;
        $adminAttendanceRecord = $adminUser->attendanceRecords()->create([
            'id' => $adminAttendanceRecordId,
            'date' => $adminFormattedDate,
            'clock_in' => $adminFormattedTimeClockIn,
            'clock_out' => $adminFormattedTimeClockOut,
        ]);

        $adminAttendanceRecord->breakTimes()->create([
            'break_in' => $adminFormattedTimeBreakIn,
            'break_out' => $adminFormattedTimeBreakOut,
        ]);

        $adminApplicationRecordId = 2;
        $adminApplicationData = [
            'id' => $adminApplicationRecordId,
            'new_clock_in' => '10:00:00',
            'new_clock_out' => '19:00:00',
            'new_break_in' => ['13:00:00', '14:00:00'],
            'new_break_out' => ['13:30:00', '14:30:00'],
            'comment' => 'admin',
        ];

        // 管理者の申請登録
        $response = $this->actingAs($adminUser)->post(route('app.attendance.show', $adminAttendanceRecordId), $adminApplicationData);

        Application::findOrFail($adminApplicationRecordId)->update([
            'approval_status' => '承認済み',
        ]);

        // 申請詳細確認(一般ユーザー申請分)
        $response = $this->actingAs($adminUser)->get(route('stamp.correction.request.approve.show', $generalApplicationRecordId));

        $response->assertStatus(200);

        $response->assertSee($generalUser->name);
        $response->assertSee(now()->subDay()->format('Y年'));
        $response->assertSee(now()->subDay()->format('n月j日'));
        $response->assertSee($generalApplicationData['new_clock_in']);
        $response->assertSee($generalApplicationData['new_clock_out']);
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_in'][0])->format('H:i'));
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_out'][0])->format('H:i'));
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_in'][1])->format('H:i'));
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_out'][1])->format('H:i'));
        $response->assertSee($generalApplicationData['comment']);
        $response->assertSee('承認');

        // 申請詳細確認(管理者申請分)
        $response = $this->actingAs($adminUser)->get(route('stamp.correction.request.approve.show', $adminApplicationRecordId));

        $response->assertStatus(200);

        $response->assertSee($adminUser->name);
        $response->assertSee(now()->subDays(2)->format('Y年'));
        $response->assertSee(now()->subDays(2)->format('n月j日'));
        $response->assertSee($adminApplicationData['new_clock_in']);
        $response->assertSee($adminApplicationData['new_clock_out']);
        $response->assertSee(Carbon::parse($adminApplicationData['new_break_in'][0])->format('H:i'));
        $response->assertSee(Carbon::parse($adminApplicationData['new_break_out'][0])->format('H:i'));
        $response->assertSee(Carbon::parse($adminApplicationData['new_break_in'][1])->format('H:i'));
        $response->assertSee(Carbon::parse($adminApplicationData['new_break_out'][1])->format('H:i'));
        $response->assertSee($adminApplicationData['comment']);
        $response->assertSee('承認済み');
    }

    /**
     * @test
     * 項目：勤怠情報修正機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 修正申請の詳細画面で「承認」ボタンを押す
     */
    public function 修正申請の承認処理が正しく行われる(): void
    {
        $generalUser = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        $adminUser = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 一般ユーザーの勤怠情報登録
        $generalFormattedDate = now()->subDay()->format('Y-m-d');
        $generalFormattedTimeClockIn = now()->subDay()->format('H:i:s');
        $generalFormattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $generalFormattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $generalFormattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $generalAttendanceRecordId = 1;
        $generalAttendanceRecord = $generalUser->attendanceRecords()->create([
            'id' => $generalAttendanceRecordId,
            'date' => $generalFormattedDate,
            'clock_in' => $generalFormattedTimeClockIn,
            'clock_out' => $generalFormattedTimeClockOut,
        ]);

        $generalAttendanceRecord->breakTimes()->create([
            'break_in' => $generalFormattedTimeBreakIn,
            'break_out' => $generalFormattedTimeBreakOut,
        ]);

        $generalApplicationRecordId = 1;
        $generalApplicationData = [
            'id' => $generalApplicationRecordId,
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'general',
        ];

        // 一般ユーザー申請登録
        $response = $this->actingAs($generalUser)->post(route('app.attendance.show', $generalAttendanceRecordId), $generalApplicationData);

        // 申請詳細確認(一般ユーザー申請分)
        $response = $this->actingAs($adminUser)->get(route('stamp.correction.request.approve.show', $generalApplicationRecordId));

        $response->assertStatus(200);

        $response->assertSee($generalUser->name);
        $response->assertSee(now()->subDay()->format('Y年'));
        $response->assertSee(now()->subDay()->format('n月j日'));
        $response->assertSee($generalApplicationData['new_clock_in']);
        $response->assertSee($generalApplicationData['new_clock_out']);
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_in'][0])->format('H:i'));
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_out'][0])->format('H:i'));
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_in'][1])->format('H:i'));
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_out'][1])->format('H:i'));
        $response->assertSee($generalApplicationData['comment']);
        $response->assertSee('承認');

        // 承認処理
        $response = $this->actingAs($adminUser)->post(route('stamp.correction.request.approve.update', $generalApplicationRecordId));

        $response->assertRedirect(route('stamp.correction.request.index'));

        $response = $this->actingAs($adminUser)->get(route('admin.attendance.show', $generalAttendanceRecordId));

        $response->assertStatus(200);

        $response->assertSee($generalUser->name);
        $response->assertSee(now()->subDay()->format('Y'));
        $response->assertSee(now()->subDay()->format('m-d'));
        $response->assertSee($generalApplicationData['new_clock_in']);
        $response->assertSee($generalApplicationData['new_clock_out']);
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_in'][0])->format('H:i'));
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_out'][0])->format('H:i'));
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_in'][1])->format('H:i'));
        $response->assertSee(Carbon::parse($generalApplicationData['new_break_out'][1])->format('H:i'));
        $response->assertSee($generalApplicationData['comment']);
        $response->assertSee('修正');
    }
}
