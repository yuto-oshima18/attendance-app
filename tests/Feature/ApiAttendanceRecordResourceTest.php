<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAttendanceRecordResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // データベースのシーダを全て実行
        $this->artisan('db:seed', [
            '--class' => 'DatabaseSeeder',
        ]);
    }

    /**
     * @test
     * 項目：公開API 読み取り系
     *
     * 1. シーディングで勤怠データを作成
     * 2. GET /api/v1/attendance-records を実行く
     */
    public function ge_t_api_v1_attendance_recordsで勤怠一覧が_jso_nで取得できる(): void
    {
        $response = $this->getJson(route('api.attendance-records.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'user' => [
                        'id',
                        'user_name',
                    ],
                    'date',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                    'breaks' => [
                        '*' => [
                            'id',
                            'break_in',
                            'break_out',
                        ],
                    ],
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 読み取り系
     *
     * 1. 勤怠データを作成
     * 2. GET /api/v1/attendance-records/{id} を実行
     */
    public function ge_t_api_v1_attendance_records_attendance_record_で勤怠詳細が_jso_nで取得できる(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // ユーザーの勤怠情報登録(シーダー登録との重複は回避)
        $formattedDate = now()->addMonth()->format('Y-m-d');
        $formattedTimeClockIn = now()->addMonth()->format('H:i:s');
        $formattedTimeClockOut = now()->addMonth()->addHours(9)->format('H:i:s');
        $formattedTimeBreakIn = now()->addMonth()->addHours(3)->format('H:i:s');
        $formattedTimeBreakOut = now()->addMonth()->addHours(4)->format('H:i:s');
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

        $attendanceRecord->applications()->create([
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => 'test',
            'approval_status' => '承認待ち',
            'application_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->getJson(route('api.attendance-records.show', $attendanceRecordId));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'user' => [
                    'id',
                    'user_name',
                ],
                'date',
                'clock_in',
                'clock_out',
                'total_time',
                'total_break_time',
                'comment',
                'breaks' => [
                    '*' => [
                        'id',
                        'break_in',
                        'break_out',
                    ],
                ],
                'applications' => [
                    '*' => [
                        'id',
                        'new_clock_in',
                        'new_clock_out',
                        'comment',
                        'approval_status',
                        'application_date',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 読み取り系
     *
     * 1. GET /api/v1/attendance-records/99999 を実行
     */
    public function 存在しない_i_dでは404とエラー_jso_nが返る(): void
    {
        $response = $this->getJson(route('api.attendance-records.show', 99999));

        $response->assertStatus(404);
        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系
     *
     * 1. 正常なデータで POST /api/v1/attendance-records を実行
     */
    public function pos_t_api_v1_attendance_recordsで勤怠が作成される(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '10:00:00';
        $clockOut = '22:00:00';
        $comment = 'test';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'user' => [
                    'id',
                    'user_name',
                ],
                'date',
                'clock_in',
                'clock_out',
                'total_time',
                'total_break_time',
                'comment',
                'breaks' => [
                    '*' => [
                        'id',
                        'break_in',
                        'break_out',
                    ],
                ],
            ],
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => 4,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系
     *
     * 1. 既存勤怠に対して PUT で更新データを送信
     * 2. 存在しない ID に対して PUT を実行
     */
    public function pu_t_api_v1_attendance_records_attendance_record_で勤怠が更新される(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '10:00:00';
        $clockOut = '22:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateClockIn = '11:00:00';
        $updateClockOut = '23:00:00';
        $updateComment = 'update';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $date,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => 4,
            'date' => $date,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response = $this->putJson(route('api.attendance-records.update', 99999), [
            'date' => $date,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系
     *
     * 1. 既存勤怠に対して DELETE を送信
     * 2. 存在しない ID に対して DELETE を実行
     */
    public function delet_e_api_v1_attendance_records_attendance_record_で勤怠が削除される(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '10:00:00';
        $clockOut = '22:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $response = $this->deleteJson(route('api.attendance-records.destroy', $attendanceId));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceId,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response = $this->deleteJson(route('api.attendance-records.update', 99999));

        $response->assertStatus(404);
        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }

    /**
     * @test
     * 項目：Sanctum 認証
     *
     * 1. 認証なしで POST/PUT/DELETE /api/v1/attendance-records を実行
     */
    public function 未認証時に書き込み系_ap_iで401が返る(): void
    {
        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '10:00:00';
        $clockOut = '22:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        $response = $this->putJson(route('api.attendance-records.update', 1));

        $response->assertStatus(401);

        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);

        $response = $this->deleteJson(route('api.attendance-records.destroy', 1));

        $response->assertStatus(401);

        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * @test
     * 項目：Sanctum 認証
     *
     * 1. Sanctum::actingAs($user) で認証
     * 2. 自分の勤怠に対して PUT/DELETE を実行
     */
    public function 認証済みユーザーは自分の勤怠を更新・削除できる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '10:00:00';
        $clockOut = '22:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateClockIn = '11:00:00';
        $updateClockOut = '23:00:00';
        $updateComment = 'update';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $date,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => 4,
            'date' => $date,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response = $this->deleteJson(route('api.attendance-records.destroy', $attendanceId));

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceId,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(204);
    }

    /**
     * @test
     * 項目：Sanctum 認証
     *
     * 1. Sanctum::actingAs($user) で認証
     * 2. 他ユーザーの勤怠に対して PUT/DELETE を実行
     */
    public function 他ユーザーの勤怠を更新・削除しようとすると403が返る(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '10:00:00';
        $clockOut = '22:00:00';
        $comment = 'store';

        $updateClockIn = '11:00:00';
        $updateClockOut = '23:00:00';
        $updateComment = 'update';

        $response = $this->putJson(route('api.attendance-records.update', 1), [
            'date' => $date,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(403);

        $response = $this->deleteJson(route('api.attendance-records.destroy', 1));

        $response->assertStatus(403);
    }

    /**
     * @test
     * 項目：公開API 読み取り系: 追加分テスト
     *
     * 1. 勤怠データを作成
     * 2. GET /api/v1/attendance-recordsをリクエストパラメータ付きで実行
     */
    public function 勤怠リスト取得にて「user_id・date・month・page・per_page」を入力し正常に取得される_値は下限で入力(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'attendance_status' => '勤務外',
            'email_verified_at' => now(),
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = '2026-01-01';
        $formattedTimeClockIn = '09:00:00';
        $formattedTimeClockOut = '18:00:00';
        $attendanceRecordId = 1;
        $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        $response = $this->getJson(route('api.attendance-records.index').'?user_id=4&date=2026-01-01&month=2026-01&page=1&per_page=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'user' => [
                        'id',
                        'user_name',
                    ],
                    'date',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                    'breaks' => [
                        '*' => [
                            'id',
                            'break_in',
                            'break_out',
                        ],
                    ],
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト登録にて「date」を入力しない
     */
    public function 勤怠リスト登録にて「date」を入力せず422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = '';
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'test';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'date' => [
                    '勤怠日は必須です。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト登録にて「date」に不正なYMDを入力
     */
    public function 勤怠リスト登録にて「date」に不正な_ym_dを入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = '2026:01:01';
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'test';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'date' => [
                    '勤怠日は YYYY-MM-DD 形式で指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト登録にて「date」に既に登録している日付を入力
     */
    public function 勤怠リスト登録にて「date」に既に登録している日付を入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = '2026-01-01';
        $formattedTimeClockIn = '09:00:00';
        $formattedTimeClockOut = '18:00:00';
        $attendanceRecordId = 1;
        $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = '2026-01-01';
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'test';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'date' => [
                    'この日付の勤怠は既に登録されています。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト登録にて「clock_in」を入力しない
     */
    public function 勤怠リスト登録にて「clock_in」を入力せず422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = '2026-01-01';
        $clockIn = '';
        $clockOut = '18:00:00';
        $comment = 'test';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_in' => [
                    '出勤時刻は必須です。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト登録にて「clock_in」に不正なHisを入力
     */
    public function 勤怠リスト登録にて「clock_in」に不正な_hisを入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = '2026-01-01';
        $clockIn = '09-00-00';
        $clockOut = '18:00:00';
        $comment = 'test';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_in' => [
                    '出勤時刻は HH:MM:SS 形式で指定してください。',
                    '出勤時刻は退勤時刻より前の時刻を指定してください。',
                ],
                'clock_out' => [
                    '退勤時刻は出勤時刻より後の時刻を指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト登録にて「clock_in」を「clock_out」より後の時間で入力
     */
    public function 勤怠リスト登録にて「clock_in」を「clock_out」より後の時間で入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = '2026-01-01';
        $clockIn = '19:00:00';
        $clockOut = '18:00:00';
        $comment = 'test';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_in' => [
                    '出勤時刻は退勤時刻より前の時刻を指定してください。',
                ],
                'clock_out' => [
                    '退勤時刻は出勤時刻より後の時刻を指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト登録にて「clock_out」に不正なHisを入力
     */
    public function 勤怠リスト登録にて「clock_out」に不正な_hisを入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = '2026-01-01';
        $clockIn = '09:00:00';
        $clockOut = '18-00-00';
        $comment = 'test';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_out' => [
                    '退勤時刻は HH:MM:SS 形式で指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト登録にて「clock_out」を「clock_in」より前の時間で入力
     */
    public function 勤怠リスト登録にて「clock_out」を「clock_in」より前の時間で入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = '2026-01-01';
        $clockIn = '09:00:00';
        $clockOut = '08:00:00';
        $comment = 'test';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_in' => [
                    '出勤時刻は退勤時刻より前の時刻を指定してください。',
                ],
                'clock_out' => [
                    '退勤時刻は出勤時刻より後の時刻を指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト登録にて「comment」に256文字入力
     */
    public function 勤怠リスト登録にて「comment」に256文字入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $commentMaxOver = str_repeat('a', 256);

        $date = '2026-01-01';
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = $commentMaxOver;

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'comment' => [
                    '備考は 255 文字以内で入力してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト更新にて「date」を入力しない
     */
    public function 勤怠リスト更新にて「date」を入力せず422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateDate = '';
        $updateClockIn = '10:00:00';
        $updateClockOut = '19:00:00';
        $updateComment = 'test';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $updateDate,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'date' => [
                    '勤怠日は必須です。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト更新にて「date」に不正なYMDを入力
     */
    public function 勤怠リスト更新にて「date」に不正な_ym_dを入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateDate = '2026:01:01';
        $updateClockIn = '10:00:00';
        $updateClockOut = '19:00:00';
        $updateComment = 'test';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $updateDate,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'date' => [
                    '勤怠日は YYYY-MM-DD 形式で指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト更新にて「date」に既に登録している更新対象勤怠の日付以外の日付を入力
     */
    public function 勤怠リスト更新にて「date」に既に登録している更新対象勤怠の日付以外の日付を入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        // ユーザーの勤怠情報登録
        $formattedDate = '2026-01-01';
        $formattedTimeClockIn = '09:00:00';
        $formattedTimeClockOut = '18:00:00';
        $attendanceRecordId = 1;
        $user->attendanceRecords()->create([
            'id' => $attendanceRecordId,
            'date' => $formattedDate,
            'clock_in' => $formattedTimeClockIn,
            'clock_out' => $formattedTimeClockOut,
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateDate = '2026-01-01';
        $updateClockIn = '10:00:00';
        $updateClockOut = '19:00:00';
        $updateComment = 'test';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $updateDate,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'date' => [
                    'この日付の勤怠は既に登録されています。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト更新にて「clock_in」を入力しない
     */
    public function 勤怠リスト更新にて「clock_in」を入力せず422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateDate = '2026-01-01';
        $updateClockIn = '';
        $updateClockOut = '19:00:00';
        $updateComment = 'test';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $updateDate,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_in' => [
                    '出勤時刻は必須です。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト更新にて「clock_in」に不正なHisを入力
     */
    public function 勤怠リスト更新にて「clock_in」に不正な_hisを入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateDate = '2026-01-01';
        $updateClockIn = '10-00-00';
        $updateClockOut = '19:00:00';
        $updateComment = 'test';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $updateDate,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_in' => [
                    '出勤時刻は HH:MM:SS 形式で指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト更新にて「clock_in」を「clock_out」より後の時間で入力
     */
    public function 勤怠リスト更新にて「clock_in」を「clock_out」より後の時間で入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateDate = '2026-01-01';
        $updateClockIn = '20:00:00';
        $updateClockOut = '19:00:00';
        $updateComment = 'test';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $updateDate,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_in' => [
                    '出勤時刻は退勤時刻より前の時刻を指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト更新にて「clock_out」に不正なHisを入力
     */
    public function 勤怠リスト更新にて「clock_out」に不正な_hisを入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateDate = '2026-01-01';
        $updateClockIn = '10:00:00';
        $updateClockOut = '19-00-00';
        $updateComment = 'test';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $updateDate,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_out' => [
                    '退勤時刻は HH:MM:SS 形式で指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト更新にて「clock_out」を「clock_in」より前の時間で入力
     */
    public function 勤怠リスト更新にて「clock_out」を「clock_in」より前の時間で入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $updateDate = '2026-01-01';
        $updateClockIn = '10:00:00';
        $updateClockOut = '09:00:00';
        $updateComment = 'test';

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $updateDate,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'clock_out' => [
                    '退勤時刻は出勤時刻より後の時刻を指定してください。',
                ],
            ],
        ]);
    }

    /**
     * @test
     * 項目：公開API 書き込み系: 追加分テスト
     *
     * 1. 勤怠リスト更新にて「date」に256文字入力
     */
    public function 勤怠リスト更新にて「date」に256文字入力し422エラーが返り、errorsフィールドに日本語のエラーメッセージが含まれる(): void
    {
        $user = User::factory()->create([
            'id' => 4,
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $date = now()->addMonth()->format('Y-m-d');
        $clockIn = '09:00:00';
        $clockOut = '18:00:00';
        $comment = 'store';

        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        // 登録後のレスポンス内のidを取り出す
        $attendanceId = $response->json('data.id');

        $commentMaxOver = str_repeat('a', 256);

        $updateDate = '2026-01-01';
        $updateClockIn = '10:00:00';
        $updateClockOut = '19:00:00';
        $comment = $commentMaxOver;

        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'date' => $updateDate,
            'clock_in' => $updateClockIn,
            'clock_out' => $updateClockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'comment' => [
                    '備考は 255 文字以内で入力してください。',
                ],
            ],
        ]);
    }
}
