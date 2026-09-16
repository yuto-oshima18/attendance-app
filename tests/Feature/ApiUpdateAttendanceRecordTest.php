<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiUpdateAttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * UpdateAttendanceRecordRequestの正常系テスト
     * Unitテストで実施不可のためFeatureテストにて実施
     */
    public function 勤怠リスト更新のバリデーションでbodyから「date・clock_in」を除き「clock_out・comment」を入力し正常にフィルタされる(): void
    {
        $user = User::factory()->create([
            'id' => 1,
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

        $updateClockOut = '23:00:00';
        $updateComment = 'update';

        // date・clock_inを除く
        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), [
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => 1,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $updateClockOut,
            'comment' => $updateComment,
        ]);
    }

    /**
     * @dataProvider failsUpdateValidateValueProvider
     *
     * UpdateAttendanceRecordRequestの異常系テスト
     * Unitテストで実施不可のためFeatureテストにて実施
     *
     * @param  array  $value  データプロバイダーから受け取るテストデータ
     */
    public function test_update_validate_fails(array $value): void
    {
        $user = User::factory()->create([
            'id' => 1,
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

        // 異なる日付の勤怠も作成
        $response = $this->postJson(route('api.attendance-records.store'), [
            'date' => '2999-01-01',
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => $comment,
        ]);

        $response->assertStatus(201);

        $data = $value;

        // date・clock_inを除く
        $response = $this->putJson(route('api.attendance-records.update', $attendanceId), $data);

        $response->assertStatus(422);
    }

    public static function failsUpdateValidateValueProvider(): array
    {
        $date = now()->addMonth()->format('Y-m-d');
        $commentMaxOver = str_repeat('a', 256);

        return [
            '勤怠リスト更新のバリデーションで「date」を入力せず、バリデーションエラーとなる' => [
                [
                    'date' => '',
                    'clock_in' => '10:00:00',
                    'clock_out' => '19:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト更新のバリデーションで「date」にY:m:dを入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026:01:01',
                    'clock_in' => '10:00:00',
                    'clock_out' => '19:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト更新のバリデーションで「date」に同じユーザーで既に存在する勤怠の日付を入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2999-01-01',
                    'clock_in' => '10:00:00',
                    'clock_out' => '19:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト更新のバリデーションで「clock_in」を入力せず、バリデーションエラーとなる' => [
                [
                    'date' => $date,
                    'clock_in' => '',
                    'clock_out' => '19:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト更新のバリデーションで「clock_in」にH-i-sを入力し、バリデーションエラーとなる' => [
                [
                    'date' => $date,
                    'clock_in' => '10-00-00',
                    'clock_out' => '19:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト更新のバリデーションで「date・clock_in・clock_out」を入力の際、clock_inはclock_outより後の時間で入力し、バリデーションエラーとなる' => [
                [
                    'date' => $date,
                    'clock_in' => '20:00:00',
                    'clock_out' => '19:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト更新のバリデーションで「clock_out」にH-i-sを入力し、バリデーションエラーとなる' => [
                [
                    'date' => $date,
                    'clock_in' => '10:00:00',
                    'clock_out' => '19-00-00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト更新のバリデーションで「date・clock_in・clock_out」を入力の際、clock_outはclock_inより前の時間で入力し、バリデーションエラーとなる' => [
                [
                    'date' => $date,
                    'clock_in' => '10:00:00',
                    'clock_out' => '09-00-00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト更新のバリデーションで「comment」に数値を入力し、バリデーションエラーとなる' => [
                [
                    'date' => $date,
                    'clock_in' => '10:00:00',
                    'clock_out' => '19-00-00',
                    'comment' => 1,
                ],
            ],

            // 以下、境界値テスト
            '勤怠リスト更新のバリデーションで「comment」に256を入力し、バリデーションエラーとなる' => [
                [
                    'date' => $date,
                    'clock_in' => '10:00:00',
                    'clock_out' => '19-00-00',
                    'comment' => $commentMaxOver,
                ],
            ],
        ];
    }
}
