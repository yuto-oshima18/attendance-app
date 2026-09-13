<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 項目：ユーザー情報取得機能（管理者）
     *
     * 1. 管理者でログインする
     * 2. スタッフ一覧ページを開く
     */
    public function 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる(): void
    {
        // 管理者情報作成
        $userAdmin = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 一般ユーザー情報作成
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        // スタッフ一覧を表示
        $response = $this->actingAs($userAdmin)->get(route('admin.staff.index'));

        $response->assertStatus(200);

        // 管理者確認
        $response->assertSee($userAdmin->name);
        $response->assertSee($userAdmin->email);

        // 一般ユーザー確認
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    /**
     * @test
     * 項目：勤怠詳細情報取得・修正機能（管理者）
     *
     * 1. 管理者ユーザーでログインする
     * 2. 選択したユーザーの勤怠一覧ページを開く
     */
    public function ユーザーの勤怠情報が正しく表示される(): void
    {
        // ID:1
        $adminUser = User::factory()->create([
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        $generalUserId = 2;
        // ID:2
        $generalUser = User::factory()->create([
            'id' => $generalUserId,
            'attendance_status' => '勤務外',
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = now()->format('Y-m-d');
        $formattedTimeClockIn = now()->format('H:i:s');
        $formattedTimeClockOut = now()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->addHours(4)->format('H:i:s');
        $attendanceRecordId = 1;
        $attendanceRecord = $generalUser->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $attendanceRecord->breakTimes()->create([
            'break_in' => $formattedTimeBreakIn,
            'break_out' => $formattedTimeBreakOut,
        ]);

        $formattedDateYearMonth = now()->format('Y/m');

        $response = $this->actingAs($adminUser)->get(route('admin.attendance.staff.show', $generalUserId));

        $response->assertStatus(200);
        $response->assertSee($generalUser->name);
        $response->assertSee($formattedDateYearMonth);
        $response->assertSee($formattedDate);
        $response->assertSee($formattedTimeClockIn);
        $response->assertSee($formattedTimeClockOut);
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * @test
     * 項目：ユーザー情報取得機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「前月」ボタンを押す
     */
    public function 「前月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $userId = 1;
        $user = User::factory()->create([
            'id' => $userId,
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 前月分の打刻登録 9時間勤務　1時間休憩
        $formattedDateSubMonth = now()->subMonth()->format('Y-m-d');
        $clockInSubMonth = now()->subMonth()->format('H:i');
        $clockOutSubMonth = now()->subMonth()->addHours(9)->format('H:i');
        $breakInSubMonth = now()->subMonth()->addHours(3)->format('H:i');
        $breakOutSubMonth = now()->subMonth()->addHours(4)->format('H:i');

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
        $response = $this->actingAs($user)->get(route('admin.attendance.staff.show', $userId)."?date=$queryParamDate");

        $response->assertStatus(200);

        $formattedDateYearMonth = now()->subMonth()->format('Y/m');

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($formattedDateYearMonth);
        $response->assertSee($formattedDateSubMonth);
        $response->assertSee($clockInSubMonth);
        $response->assertSee($clockOutSubMonth);
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * @test
     * 項目：ユーザー情報取得機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「翌月」ボタンを押す
     */
    public function 「翌月」を押下した時に表示月の前月の情報が表示される(): void
    {
        $userId = 1;
        $user = User::factory()->create([
            'id' => $userId,
            'attendance_status' => '勤務外',
            'admin_status' => true,
        ]);

        // 前月分の打刻登録 9時間勤務　1時間休憩
        $formattedDateAddMonth = now()->addMonth()->format('Y-m-d');
        $clockInAddMonth = now()->addMonth()->format('H:i');
        $clockOutAddMonth = now()->addMonth()->addHours(9)->format('H:i');
        $breakInAddMonth = now()->addMonth()->addHours(3)->format('H:i');
        $breakOutAddMonth = now()->addMonth()->addHours(4)->format('H:i');

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
        $response = $this->actingAs($user)->get(route('admin.attendance.staff.show', $userId)."?date=$queryParamDate");

        $response->assertStatus(200);

        $formattedDateYearMonth = now()->addMonth()->format('Y/m');

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($formattedDateYearMonth);
        $response->assertSee($formattedDateAddMonth);
        $response->assertSee($clockInAddMonth);
        $response->assertSee($clockOutAddMonth);
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * @test
     * 項目：ユーザー情報取得機能（管理者）
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * 3. 「詳細」ボタンを押下する
     */
    public function 「詳細」を押下すると、その日の勤怠詳細画面に遷移する(): void
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
            'comment' => 'test',
        ]);

        $generalAttendanceRecord->breakTimes()->create([
            'break_in' => $generalFormattedTimeBreakIn,
            'break_out' => $generalFormattedTimeBreakOut,
        ]);

        $response = $this->actingAs($adminUser)->get(route('admin.attendance.show', $generalAttendanceRecordId));

        $response->assertStatus(200);

        $response->assertSee($generalUser->name);
        $response->assertSee(now()->subDay()->format('Y'));
        $response->assertSee(now()->subDay()->format('m-d'));
        $response->assertSee($generalFormattedTimeClockIn);
        $response->assertSee($generalFormattedTimeClockOut);
        $response->assertSee(Carbon::parse($generalFormattedTimeBreakIn)->format('H:i'));
        $response->assertSee(Carbon::parse($generalFormattedTimeBreakOut)->format('H:i'));
        $response->assertSee('test');
        $response->assertSee('修正');
    }

    /**
     * @test
     * 項目：ユーザー情報取得機能（管理者）:追加分テスト
     *
     * 1. 管理者ユーザーにログインをする
     * 2. 勤怠一覧ページに存在する「CSV出力」ボタンを押下する
     */
    public function スタッフの勤怠詳細リストを月毎に_cs_vへエクスポートできる(): void
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
            'comment' => 'test',
        ]);

        $generalAttendanceRecord->breakTimes()->create([
            'break_in' => $generalFormattedTimeBreakIn,
            'break_out' => $generalFormattedTimeBreakOut,
        ]);

        $response = $this->actingAs($adminUser)->post(route('export').'?'.http_build_query([
            'user_id' => $generalUser->id,
            'year_month' => now()->subDay()->format('Y-m'),
        ]));

        $response->assertStatus(200);

        $content = $response->streamedContent();

        // BOMが付いていることを確認
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // BOMを除去
        $content = substr($content, 3);

        // CSVを行ごとに配列化
        $lines = array_map(
            'str_getcsv',
            preg_split('/\r\n|\r|\n/', trim($content))
        );

        // 1行目：ユーザー名・対象月
        $this->assertSame([
            $generalUser->name,
            now()->subDay()->format('Y年m月'),
        ], $lines[0]);

        // 2行目：ヘッダー
        $this->assertSame([
            '日付',
            '出勤',
            '退勤',
            '休憩',
            '合計',
        ], $lines[1]);

        // 3行目：勤怠データ
        $this->assertSame([
            now()->subDay()->format('Y-m-d'),
            $generalFormattedTimeClockIn,
            $generalFormattedTimeClockOut,
            '1:00',
            '8:00',
        ], $lines[2]);

        // 列数が5列であることを確認
        $this->assertCount(5, $lines[1]);
        $this->assertCount(5, $lines[2]);
    }
}
