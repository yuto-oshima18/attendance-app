<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApiIndexAttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider passesIndexValidateValueProvider
     *
     * IndexAttendanceRecordRequestの正常系テスト
     *
     * @param  array  $value  データプロバイダーから受け取るテストデータ
     */
    public function test_index_validate_passes(array $value): void
    {
        $request = new IndexAttendanceRecordRequest;
        $rules = $request->rules();

        $data = $value;

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());
    }

    public static function passesIndexValidateValueProvider(): array
    {
        return [
            '勤怠リスト取得のバリデーションで「user_id・date・month・page・per_page」を入力し正常にフィルタされる(値は下限で入力)' => [
                [
                    'user_id' => 4,
                    'date' => '2026-01-01',
                    'month' => '2026-01',
                    'page' => 1,
                    'per_page' => 1,
                ],
            ],

            '勤怠リスト取得のバリデーションで「per_page」を入力し正常にフィルタされる(値は上限で入力)' => [
                [
                    'per_page' => 100,
                ],
            ],

            '勤怠リスト取得のバリデーションで「user_id・date・month・page・per_page」を入力せず、正常にレスポンスされる' => [
                [
                    // リクエストパラメータなし
                ],
            ],
        ];
    }

    /**
     * @dataProvider failsIndexValidateValueProvider
     *
     * IndexAttendanceRecordRequestの異常系テスト
     *
     * @param  array  $value  データプロバイダーから受け取るテストデータ
     */
    public function test_index_validate_fails(array $value): void
    {
        $request = new IndexAttendanceRecordRequest;
        $rules = $request->rules();

        $data = $value;

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
    }

    public static function failsIndexValidateValueProvider(): array
    {
        return [
            '勤怠リスト取得のバリデーションで「user_id」に文字列を入力し、バリデーションエラーとなる' => [
                [
                    'user_id' => 'test',
                ],
            ],

            '勤怠リスト取得のバリデーションで「date」にY:m:dを入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026:01:01',
                ],
            ],

            '勤怠リスト取得のバリデーションで「month」にY:m:を入力し、バリデーションエラーとなる' => [
                [
                    'date' => '2026:01',
                ],
            ],

            '勤怠リスト取得のバリデーションで「page」に文字列を入力し、バリデーションエラーとなる' => [
                [
                    'page' => 'test',
                ],
            ],

            '勤怠リスト取得のバリデーションで「per_page」に文字列を入力し、バリデーションエラーとなる' => [
                [
                    'per_page' => 'test',
                ],
            ],

            // 以下、境界値テスト
            '勤怠リスト取得のバリデーションで「user_id」に0を入力し、バリデーションエラーとなる' => [
                [
                    'user_id' => 0,
                ],
            ],

            '勤怠リスト取得のバリデーションで「page」に0を入力し、バリデーションエラーとなる' => [
                [
                    'page' => 0,
                ],
            ],

            '勤怠リスト取得のバリデーションで「per_page」に0を入力し、バリデーションエラーとなる' => [
                [
                    'per_page' => 0,
                ],
            ],

            '勤怠リスト取得のバリデーションで「per_page」に101を入力し、バリデーションエラーとなる' => [
                [
                    'per_page' => 101,
                ],
            ],
        ];
    }
}
