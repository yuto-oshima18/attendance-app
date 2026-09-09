<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 出勤時間を退勤時間より後に設定する
     * 4. 保存処理をする
     */
    public function 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '19:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 休憩開始時間を退勤時間より後に設定する
     * 4. 保存処理をする
     */
    public function 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['19:00:00', '20:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
            'new_break_in.1' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 休憩終了時間を退勤時間より後に設定する
     * 4. 保存処理をする
     */
    public function 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['19:00:00', '20:00:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
            'new_break_out.1' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 備考欄を未入力のまま保存処理をする
     */
    public function 備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => '',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）:追加分テスト
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 出勤時間を未入力のまま保存処理をする
     */
    public function 出勤時間が未入力の場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間は必須です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）:追加分テスト
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 出勤時間に文字列を記載し保存処理をする
     */
    public function 出勤時間に文字列が記載されている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => 'test',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間はH:i:sで入力してください',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）:追加分テスト
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 退勤時間に文字列を記載し保存処理をする
     */
    public function 退勤時間に文字列が記載されている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => 'test',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_out' => '退勤時間はH:i:sで入力してください',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）:追加分テスト
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 退勤時間に出勤時間より前の時間を記載し保存処理をする
     */
    public function 退勤時間が出勤時間より前になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '08:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）:追加分テスト
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 休憩入に出勤時間より前の時間を記載し保存処理をする
     */
    public function 休憩入が出勤時間より前になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['07:00:00', '06:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
            'new_break_in.1' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）:追加分テスト
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 備考に256文字の文字列を記載し保存処理をする
     */
    public function 備考に256文字の文字列が記載されている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $commentMaxOver = str_repeat('a', 256);
        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => $commentMaxOver,
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'comment' => '備考は255文字までで入力してください',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報修正機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細を修正し保存処理をする
     * 3. 申請一覧画面を確認する
     */
    public function 「承認待ち」にログインユーザーが行った申請が全て表示されていること(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('app.attendance.show', $attendanceRecordId), $data);

        $response->assertRedirect(route('stamp_correction_request.index'));

        $response = $this->actingAs($user)->get(route('stamp_correction_request.index'));

        $response->assertSee('承認待ち');
        $response->assertSee($user->name);
        $response->assertSee($formattedDate);
        $response->assertSee($data['comment']);
        $response->assertSee(now()->format('Y-m-d'));
    }
}
