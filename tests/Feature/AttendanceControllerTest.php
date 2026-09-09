<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 項目：日時取得機能
     *
     * 1. 勤怠打刻画面を開く
     * 2. 画面に表示されている日時情報を確認する
     */
    public function 現在の日時情報が_u_iと同じ形式で出力されている(): void
    {
        $user = User::factory()->create();

        $now = now();
        $this->travelTo($now);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);

        $response->assertViewHas('formattedDate', $now->format('Y-m-d'));

        $response->assertViewHas('formattedTime', $now->format('H:i:s'));
    }

    /**
     * @test
     * 項目：ステータス確認機能
     *
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     */
    public function 勤務外の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);

        $response->assertViewHas('user', function ($user) {
            return $user->attendance_status === '勤務外';
        });
    }

    /**
     * @test
     * 項目：ステータス確認機能
     *
     * 1. ステータスが出勤中のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     */
    public function 出勤中の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '出勤中',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);

        $response->assertViewHas('user', function ($user) {
            return $user->attendance_status === '出勤中';
        });
    }

    /**
     * @test
     * 項目：ステータス確認機能
     *
     * 1. ステータスが休憩中のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     */
    public function 休憩中の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '休憩中',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);

        $response->assertViewHas('user', function ($user) {
            return $user->attendance_status === '休憩中';
        });
    }

    /**
     * @test
     * 項目：ステータス確認機能
     *
     * 1. ステータスが退勤済のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面に表示されているステータスを確認する
     */
    public function 退勤済の場合、勤怠ステータスが正しく表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '退勤済',
        ]);

        // 「退勤済」のユーザーの最終打刻日判定の回避処理
        $formattedDate = now()->format('Y-m-d');
        $formattedTime = now()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);

        $response->assertViewHas('user', function ($user) {
            return $user->attendance_status === '退勤済';
        });
    }

    /**
     * @test
     * 項目：出勤機能
     *
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 画面に「出勤」ボタンが表示されていることを確認する
     * 3. 出勤の処理を行う
     */
    public function 出勤ボタンが正しく機能する(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの最終打刻判定の回避処理
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('出勤');

        // 出勤処理を実行
        $postData = ['action' => 'clock_in'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('出勤中', $user->attendance_status);
    }

    /**
     * @test
     * 項目：出勤機能
     *
     * 1. ステータスが退勤済であるユーザーにログインする
     * 2. 勤務ボタンが表示されないことを確認する
     */
    public function 出勤は一日一回のみできる(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '退勤済',
        ]);

        // 「退勤済」のユーザーの最終打刻日判定の回避処理
        $formattedDate = now()->format('Y-m-d');
        $formattedTime = now()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertDontSee('出勤');
    }

    /**
     * @test
     * 項目：出勤機能
     *
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 出勤の処理を行う
     * 3. 勤怠一覧画面から出勤の日付を確認する
     */
    public function 出勤時刻が勤怠一覧画面で確認できる(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの最終打刻判定の回避処理
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        // 出勤処理を実行
        $postData = ['action' => 'clock_in'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('出勤中', $user->attendance_status);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertSee($user->attendanceRecords()->latest('date')->first()->clock_in);
    }

    /**
     * @test
     * 項目：休憩機能
     *
     * 1. ステータスが出勤中のユーザーにログインする
     * 2. 画面に「休憩入」ボタンが表示されていることを確認する
     * 3. 休憩の処理を行う
     */
    public function 休憩ボタンが正しく機能する(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '出勤中',
        ]);

        // ユーザーの打刻準備
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 休憩入処理を実行
        $postData = ['action' => 'break_in'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('休憩中', $user->attendance_status);
    }

    /**
     * @test
     * 項目：休憩機能
     *
     * 1. ステータスが出勤中であるユーザーにログインする
     * 2. 休憩入と休憩戻の処理を行う
     * 3. 「休憩入」ボタンが表示されることを確認する
     */
    public function 休憩は一日に何回でもできる(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '出勤中',
        ]);

        // ユーザーの打刻準備
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 休憩入処理を実行
        $postData = ['action' => 'break_in'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('休憩中', $user->attendance_status);

        // 休憩戻処理を実行
        $postData = ['action' => 'break_out'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('出勤中', $user->attendance_status);

        // 休憩入ボタンを確認
        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('休憩入');
    }

    /**
     * @test
     * 項目：休憩機能
     *
     * 1. ステータスが出勤中であるユーザーにログインする
     * 2. 休憩入の処理を行う
     * 3. 休憩戻の処理を行う
     */
    public function 休憩戻ボタンが正しく機能する(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '出勤中',
        ]);

        // ユーザーの打刻準備
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 休憩入処理を実行
        $postData = ['action' => 'break_in'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('休憩中', $user->attendance_status);

        // 休憩戻ボタンを確認
        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        // 休憩戻処理を実行
        $postData = ['action' => 'break_out'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('出勤中', $user->attendance_status);
    }

    /**
     * @test
     * 項目：休憩機能
     *
     * 1. ステータスが出勤中であるユーザーにログインする
     * 2. 休憩入と休憩戻の処理を行い、再度休憩入の処理を行う
     * 3. 「休憩戻」ボタンが表示されることを確認する
     */
    public function 休憩戻は一日に何回でもできる(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '出勤中',
        ]);

        // ユーザーの打刻準備
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('休憩入');

        // 休憩入処理を実行
        $postData = ['action' => 'break_in'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('休憩中', $user->attendance_status);

        // 休憩戻処理を実行
        $postData = ['action' => 'break_out'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('出勤中', $user->attendance_status);

        // 再度休憩入処理を実行
        $postData = ['action' => 'break_in'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('休憩中', $user->attendance_status);

        // 休憩戻ボタンを確認
        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('休憩戻');
    }

    /**
     * @test
     * 項目：休憩機能
     *
     * 1. ステータスが勤務中のユーザーにログインする
     * 2. 休憩入と休憩戻の処理を行う
     * 3. 勤怠一覧画面から休憩の日付を確認する
     */
    public function 休憩時刻が勤怠一覧画面で確認できる(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '出勤中',
        ]);

        // ユーザーの最終打刻判定の回避処理
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        // 休憩入処理を実行
        $postData = ['action' => 'break_in'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('休憩中', $user->attendance_status);

        // 休憩戻処理を実行
        $postData = ['action' => 'break_out'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('出勤中', $user->attendance_status);

        $latestAttendance = $user->attendanceRecords()->latest('date')->first();
        $latestBreakIn = $latestAttendance->breakTimes()->first()->break_in;
        $latestBreakOut = $latestAttendance->breakTimes()->first()->break_out;

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertSee($latestBreakIn);
        $response->assertSee($latestBreakOut);
    }

    /**
     * @test
     * 項目：退勤機能
     *
     * 1. ステータスが勤務中のユーザーにログインする
     * 2. 画面に「退勤」ボタンが表示されていることを確認する
     * 3. 退勤の処理を行う
     */
    public function 退勤ボタンが正しく機能する(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '出勤中',
        ]);

        // ユーザーの最終打刻判定の回避処理
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.create'));

        $response->assertStatus(200);
        $response->assertSee('退勤');

        // 退勤処理を実行
        $postData = ['action' => 'clock_out'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('退勤済', $user->attendance_status);
    }

    /**
     * @test
     * 項目：退勤機能
     *
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 出勤と退勤の処理を行う
     * 3. 勤怠一覧画面から退勤の日付を確認する
     */
    public function 退勤時刻が勤怠一覧画面で確認できる(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの最終打刻判定の回避処理
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        // 出勤処理を実行
        $postData = ['action' => 'clock_in'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('出勤中', $user->attendance_status);

        // 退勤処理を実行
        $postData = ['action' => 'clock_out'];
        $response = $this->actingAs($user)->post(route('attendance.store'), $postData);

        $response->assertRedirect(route('attendance.create'));
        $user->refresh();
        $this->assertSame('退勤済', $user->attendance_status);

        $latestAttendance = $user->attendanceRecords()->latest('date')->first();

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertSee($latestAttendance->clock_out);
    }

    /**
     * @test
     * 項目：勤怠一覧情報取得機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインする
     * 2. 勤怠一覧ページを開く
     * 3. 自分の勤怠情報がすべて表示されていることを確認する
     */
    public function 自分が行った勤怠情報が全て表示されている(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // 昨日分の打刻登録 5時間勤務　2時間休憩
        $formattedDateSubDay = now()->subDay()->format('Y-m-d');
        $clockInSubDay = now()->subDay()->format('H:i:s');
        $clockOutSubDay = now()->subDay()->addHours(5)->format('H:i:s');
        $breakInSubDay = now()->subDay()->addHours(3)->format('H:i:s');
        $breakOutSubDay = now()->subDay()->addHours(5)->format('H:i:s');

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => $formattedDateSubDay,
            'clock_in' => $clockInSubDay,
            'clock_out' => $clockOutSubDay,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $breakInSubDay,
            'break_out' => $breakOutSubDay,
        ]);

        // 本日分の打刻登録 9時間勤務　1時間休憩
        $formattedDate = now()->format('Y-m-d');
        $clockIn = now()->format('H:i:s');
        $clockOut = now()->addHours(9)->format('H:i:s');
        $breakIn = now()->addHours(3)->format('H:i:s');
        $breakOut = now()->addHours(4)->format('H:i:s');

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => $formattedDate,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $breakIn,
            'break_out' => $breakOut,
        ]);

        // 勤怠一覧を表示
        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);

        // 昨日打刻の確認
        $response->assertSee($formattedDateSubDay);
        $response->assertSee($clockInSubDay);
        $response->assertSee($clockOutSubDay);
        $response->assertSee('2:00');
        $response->assertSee('3:00');

        // 昨日打刻の確認
        $response->assertSee($formattedDate);
        $response->assertSee($clockIn);
        $response->assertSee($clockOut);
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * @test
     * 項目：勤怠一覧情報取得機能（一般ユーザー）
     *
     * 1. ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        $formattedDate = now()->format('Y/m');

        // 勤怠一覧を表示
        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertSee($formattedDate);
    }

    /**
     * @test
     * 項目：勤怠一覧情報取得機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「前月」ボタンを押す
     */
    public function 「前月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // 先月分の打刻登録 9時間勤務　1時間休憩
        $formattedDateSubMonth = now()->subMonth()->format('Y-m-d');
        $clockInSubMonth = now()->subMonth()->format('H:i:s');
        $clockOutSubMonth = now()->subMonth()->addHours(9)->format('H:i:s');
        $breakInSubMonth = now()->subMonth()->addHours(3)->format('H:i:s');
        $breakOutSubMonth = now()->subMonth()->addHours(4)->format('H:i:s');

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => $formattedDateSubMonth,
            'clock_in' => $clockInSubMonth,
            'clock_out' => $clockOutSubMonth,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $breakInSubMonth,
            'break_out' => $breakOutSubMonth,
        ]);

        // 勤怠一覧を表示
        $queryParamDate = now()->subMonth()->format('Y-m');
        $response = $this->actingAs($user)->get(route('attendance.index')."?date=$queryParamDate");

        $response->assertStatus(200);

        // 昨日打刻の確認
        $response->assertSee($formattedDateSubMonth);
        $response->assertSee($clockInSubMonth);
        $response->assertSee($clockOutSubMonth);
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * @test
     * 項目：勤怠一覧情報取得機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「翌月」ボタンを押す
     */
    public function 「翌月」を押下した時に表示月の翌月の情報が表示される(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // 先月分の打刻登録 9時間勤務　1時間休憩
        $formattedDateAddMonth = now()->addMonth()->format('Y-m-d');
        $clockInAddMonth = now()->addMonth()->format('H:i:s');
        $clockOutAddMonth = now()->addMonth()->addHours(9)->format('H:i:s');
        $breakInAddMonth = now()->addMonth()->addHours(3)->format('H:i:s');
        $breakOutAddMonth = now()->addMonth()->addHours(4)->format('H:i:s');

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => $formattedDateAddMonth,
            'clock_in' => $clockInAddMonth,
            'clock_out' => $clockOutAddMonth,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $breakInAddMonth,
            'break_out' => $breakOutAddMonth,
        ]);

        // 勤怠一覧を表示
        $queryParamDate = now()->addMonth()->format('Y-m');
        $response = $this->actingAs($user)->get(route('attendance.index')."?date=$queryParamDate");

        $response->assertStatus(200);

        // 昨日打刻の確認
        $response->assertSee($formattedDateAddMonth);
        $response->assertSee($clockInAddMonth);
        $response->assertSee($clockOutAddMonth);
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * @test
     * 項目：勤怠一覧情報取得機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「詳細」ボタンを押下する
     */
    public function 「詳細」を押下すると、その日の勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $attendanceRecordId = 1;
        $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);
        $response->assertSee(Carbon::parse($formattedDate)->format('m-d'));
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 名前欄を確認する
     */
    public function 勤怠詳細画面の「名前」がログインユーザーの氏名になっている(): void
    {
        $userName = 'test';
        $user = User::factory()->create([
            'name' => $userName,
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $attendanceRecordId = 1;
        $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);
        $response->assertSee($userName);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 日付欄を確認する
     */
    public function 勤怠詳細画面の「日付」が選択した日付になっている(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTime = now()->subDay()->format('H:i:s');
        $attendanceRecordId = 1;
        $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTime,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);
        $response->assertSee(Carbon::parse($formattedDate)->year);
        $response->assertSee(Carbon::parse($formattedDate)->format('m-d'));
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 出勤・退勤欄を確認する
     */
    public function 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->subDay()->format('Y-m-d');
        $formattedTimeClockIn = now()->subDay()->format('H:i:s');
        $formattedTimeClockOut = now()->subDay()->addHours(9)->format('H:i:s');
        $attendanceRecordId = 1;
        $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);
        $response->assertSee($formattedTimeClockIn);
        $response->assertSee($formattedTimeClockOut);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得機能（一般ユーザー）
     *
     * 1. 勤怠情報が登録されたユーザーにログインをする
     * 2. 勤怠詳細ページを開く
     * 3. 休憩欄を確認する
     */
    public function 「休憩」にて記されている時間がログインユーザーの打刻と一致している(): void
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

        $response = $this->actingAs($user)->get(route('attendance.show', $attendanceRecordId));

        $response->assertStatus(200);
        $response->assertSee($formattedTimeBreakIn);
        $response->assertSee($formattedTimeBreakOut);
    }
}
