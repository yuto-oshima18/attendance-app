<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApiStoreAttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider passesStoreValidateValueProvider
     *
     * StoreAttendanceRecordRequestの正常系テスト
     *
     * @param  array  $value  データプロバイダーから受け取るテストデータ
     */
    public function test_store_validate_passes(array $value): void
    {
        $request = new StoreAttendanceRecordRequest;
        $rules = $request->rules();

        $data = $value;

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());
    }

    public static function passesStoreValidateValueProvider(): array
    {
        $commentMax = str_repeat('a', 255);

        return [
            '勤怠リスト登録のバリデーションで「date・clock_in・clock_out・comment」を入力し正常にフィルタされる(値は上限で入力)' => [
                [
                    'date' => '2026-01-01',
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'comment' => $commentMax,
                ],
            ],

            '勤怠リスト登録のバリデーションで「date・clock_in」を入力し正常にフィルタされる' => [
                [
                    'date' => '2999-01-01',
                    'clock_in' => '09:00:00',
                ],
            ],
        ];
    }

    /**
     * @dataProvider failsStoreValidateValueProvider
     *
     * StoreAttendanceRecordRequestの異常系テスト
     *
     * @param  array  $value  データプロバイダーから受け取るテストデータ
     */
    public function test_store_validate_fails(array $value): void
    {
        $request = new StoreAttendanceRecordRequest;
        $rules = $request->rules();

        $data = $value;

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
    }

    public static function failsStoreValidateValueProvider(): array
    {
        $commentMaxOver = str_repeat('a', 256);

        return [
            '勤怠リスト登録のバリデーションで「date」を入力せず、バリデーションエラーとなる' => [
                [
                    'date' => '',
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト登録のバリデーションで「date」にY:m:dを入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026:01:01',
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト登録のバリデーションで「clock_in」を入力せず、バリデーションエラーとなる' => [
                [
                    'date' => '2026-01-01',
                    'clock_in' => '',
                    'clock_out' => '18:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト登録のバリデーションで「clock_in」にH-i-sを入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026-01-01',
                    'clock_in' => '09-00-00',
                    'clock_out' => '18:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト登録のバリデーションで「date・clock_in・clock_out」を入力の際、clock_inはclock_outより後の時間で入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026-01-01',
                    'clock_in' => '19:00:00',
                    'clock_out' => '18:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト登録のバリデーションで「clock_out」にH-i-sを入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026-01-01',
                    'clock_in' => '09:00:00',
                    'clock_out' => '18-00-00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト登録のバリデーションで「date・clock_in・clock_out」を入力の際、clock_outはclock_inより前の時間で入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026-01-01',
                    'clock_in' => '09:00:00',
                    'clock_out' => '08:00:00',
                    'comment' => 'test',
                ],
            ],

            '勤怠リスト登録のバリデーションで「comment」に数値を入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026-01-01',
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'comment' => 1,
                ],
            ],

            // 以下、境界値テスト
            '勤怠リスト登録のバリデーションで「comment」に256を入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026-01-01',
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'comment' => $commentMaxOver,
                ],
            ],
        ];
    }
}
