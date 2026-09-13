<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 項目：勤怠一覧情報取得機能（管理者）
     *
     * 1. 管理者ユーザーにログインする
     * 2. 勤怠一覧画面を開く
     */
    public function その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        // 管理者情報作成
        $userAdmin = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 本日分の打刻登録 9時間勤務　1時間休憩
        $formattedDate = now()->format('Y-m-d');
        $clockIn = now()->format('H:i');
        $clockOut = now()->addHours(9)->format('H:i');
        $breakIn = now()->addHours(3)->format('H:i');
        $breakOut = now()->addHours(4)->format('H:i');

        $attendanceRecord = $userAdmin->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $breakIn,
            'break_out' => $breakOut,
        ]);

        // 一般ユーザー情報作成
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // 本日分の打刻登録 11時間勤務　2時間休憩
        $formattedDateGeneral = now()->format('Y-m-d');
        $clockInGeneral = now()->format('H:i');
        $clockOutGeneral = now()->addHours(11)->format('H:i');
        $breakInGeneral = now()->addHours(3)->format('H:i');
        $breakOutGeneral = now()->addHours(5)->format('H:i');

        $attendanceRecordGeneral = $user->attendanceRecords()->create([
            'date' => $formattedDateGeneral,
            'clock_in' => $clockInGeneral,
            'clock_out' => $clockOutGeneral,
        ]);

        $attendanceRecordGeneral->breakTimes()->create([
            'break_in' => $breakInGeneral,
            'break_out' => $breakOutGeneral,
        ]);

        // 勤怠一覧を表示
        $response = $this->actingAs($userAdmin)->get(route('admin.attendance.index'));

        $response->assertStatus(200);

        $formattedDate = now()->format('Y/m/d');

        $response->assertSee($formattedDate);

        // 本日打刻の確認(管理者)
        $response->assertSee($userAdmin->name);
        $response->assertSee($clockIn);
        $response->assertSee($clockOut);
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        // 本日打刻の確認(一般ユーザー)
        $response->assertSee($user->name);
        $response->assertSee($clockInGeneral);
        $response->assertSee($clockOutGeneral);
        $response->assertSee('2:00');
        $response->assertSee('9:00');
    }

    /**
     * @test
     * 項目：勤怠一覧情報取得機能（一般ユーザー）
     *
     * 1. 管理者ユーザーにログインする
     * 2. 勤怠一覧画面を開く
     */
    public function 遷移した際に現在の日付が表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        $formattedDate = now()->format('Y/m/d');

        // 勤怠一覧を表示
        $response = $this->actingAs($user)->get(route('admin.attendance.index'));

        $response->assertStatus(200);
        $response->assertSee($formattedDate);
    }

    /**
     * @test
     * 項目：勤怠一覧情報取得機能（一般ユーザー）
     *
     * 1. 管理者ユーザーにログインする
     * 2. 勤怠一覧画面を開く
     * 3. 「前日」ボタンを押す
     */
    public function 「前日」を押下した時に前の日の勤怠情報が表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 前日分の打刻登録 9時間勤務　1時間休憩
        $formattedDateSubDay = now()->subDay()->format('Y-m-d');
        $clockInSubDay = now()->subDay()->format('H:i');
        $clockOutSubDay = now()->subDay()->addHours(9)->format('H:i');
        $breakInSubDay = now()->subDay()->addHours(3)->format('H:i');
        $breakOutSubDay = now()->subDay()->addHours(4)->format('H:i');

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => $formattedDateSubDay,
            'clock_in' => $clockInSubDay,
            'clock_out' => $clockOutSubDay,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $breakInSubDay,
            'break_out' => $breakOutSubDay,
        ]);

        // 勤怠一覧を表示
        $queryParamDate = now()->subDay()->format('Y-m-d');
        $response = $this->actingAs($user)->get(route('admin.attendance.index')."?date=$queryParamDate");

        $response->assertStatus(200);

        $formattedDateSubDay = now()->subDay()->format('Y/m/d');

        // 昨日打刻の確認
        $response->assertSee($formattedDateSubDay);
        $response->assertSee($user->name);
        $response->assertSee($clockInSubDay);
        $response->assertSee($clockOutSubDay);
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * @test
     * 項目：勤怠一覧情報取得機能（一般ユーザー）
     *
     * 1. 管理者ユーザーにログインする
     * 2. 勤怠一覧画面を開く
     * 3. 「翌日」ボタンを押す
     */
    public function 「翌日」を押下した時に次の日の勤怠情報が表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 先月分の打刻登録 9時間勤務　1時間休憩
        $formattedDateAddDay = now()->addDay()->format('Y-m-d');
        $clockInAddDay = now()->addDay()->format('H:i');
        $clockOutAddDay = now()->addDay()->addHours(9)->format('H:i');
        $breakInAddDay = now()->addDay()->addHours(3)->format('H:i');
        $breakOutAddDay = now()->addDay()->addHours(4)->format('H:i');

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => $formattedDateAddDay,
            'clock_in' => $clockInAddDay,
            'clock_out' => $clockOutAddDay,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $breakInAddDay,
            'break_out' => $breakOutAddDay,
        ]);

        // 勤怠一覧を表示
        $queryParamDate = now()->addDay()->format('Y-m-d');
        $response = $this->actingAs($user)->get(route('admin.attendance.index')."?date=$queryParamDate");

        $response->assertStatus(200);

        $formattedDateAddDay = now()->addDay()->format('Y/m/d');

        // 昨日打刻の確認
        $response->assertSee($formattedDateAddDay);
        $response->assertSee($user->name);
        $response->assertSee($clockInAddDay);
        $response->assertSee($clockOutAddDay);
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     */
    public function 勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->format('Y-m-d');
        $formattedTimeClockIn = now()->format('H:i:s');
        $formattedTimeClockOut = now()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        $formattedDateYear = now()->format('Y');
        $formattedDateMonthDay = now()->format('m-d');

        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($formattedDateYear);
        $response->assertSee($formattedDateMonthDay);
        $response->assertSee($formattedTimeClockIn);
        $response->assertSee($formattedTimeClockOut);
        $response->assertSee($formattedTimeBreakIn);
        $response->assertSee($formattedTimeBreakOut);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 出勤時間を退勤時間より後に設定する
     * 4. 保存処理をする
     */
    public function 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '19:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 休憩開始時間を退勤時間より後に設定する
     * 4. 保存処理をする
     */
    public function 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['19:00:00', '20:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
            'new_break_in.1' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 休憩終了時間を退勤時間より後に設定する
     * 4. 保存処理をする
     */
    public function 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['19:00:00', '20:00:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
            'new_break_out.1' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 備考欄を未入力のまま保存処理をする
     */
    public function 備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => '',
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）:追加分テスト
     *
     * 1. 勤怠情報が登録された管理者ユーザーにログインをする
     * 2. 勤怠詳細を修正し保存処理をする
     * 3. 管理者ユーザーで勤怠詳細画面を確認する
     */
    public function 勤怠修正処理が実行される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        // 勤怠修正処理を実行
        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertRedirect(route('admin.attendance.index'));

        // 詳細を確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $response->assertSee($user->name);
        $response->assertSee(now()->subDay()->format('Y'));
        $response->assertSee(now()->subDay()->format('m-d'));
        $response->assertSee($data['new_clock_in']);
        $response->assertSee($data['new_clock_out']);
        $response->assertSee($data['new_break_in'][0]);
        $response->assertSee($data['new_break_out'][0]);
        $response->assertSee($data['new_break_in'][1]);
        $response->assertSee($data['new_break_out'][1]);
        $response->assertSee($data['comment']);
        $response->assertSee('修正');
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）:追加分テスト
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 出勤時間を未入力のまま保存処理をする
     */
    public function 出勤時間が未入力の場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間は必須です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）:追加分テスト
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 出勤時間に文字列を記載し保存処理をする
     */
    public function 出勤時間に文字列が記載されている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => 'test',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間はH:i:sで入力してください',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）:追加分テスト
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 退勤時間に文字列を記載し保存処理をする
     */
    public function 退勤時間に文字列が記載されている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => 'test',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_out' => '退勤時間はH:i:sで入力してください',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）:追加分テスト
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 退勤時間に出勤時間より前の時間を記載し保存処理をする
     */
    public function 退勤時間が出勤時間より前になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '08:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）:追加分テスト
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 休憩入に出勤時間より前の時間を記載し保存処理をする
     */
    public function 休憩入が出勤時間より前になっている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['07:00:00', '06:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => 'test',
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
            'new_break_in.1' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）:追加分テスト
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 備考に256文字の文字列を記載し保存処理をする
     */
    public function 備考に256文字の文字列が記載されている場合、エラーメッセージが表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->subDay()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->subDay()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        // 勤怠詳細ページの確認
        $response = $this->actingAs($user)->get(route('admin.attendance.show', $attendanceRecordId));

        $response->assertStatus(200);

        $commentMaxOver = str_repeat('a', 256);
        $data = [
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'new_break_in' => ['12:00:00', '13:00:00'],
            'new_break_out' => ['12:30:00', '13:30:00'],
            'comment' => $commentMaxOver,
        ];

        $response = $this->actingAs($user)->post(route('admin.attendance.update', $attendanceRecordId), $data);

        $response->assertSessionHasErrors([
            'comment' => '備考は255文字までで入力してください',
        ]);
    }
}
