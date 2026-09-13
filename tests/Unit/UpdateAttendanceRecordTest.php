<?php

namespace Tests\Unit;

use App\Http\Requests\UpdateAttendanceRecordRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateAttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider passesUpdateValidateValueProvider
     *
     * UpdateAttendanceRecordRequestの正常系テスト
     *
     * @param  array  $value  データプロバイダーから受け取るテストデータ
     */
    public function test_update_validate_passes(array $value): void
    {
        $request = new UpdateAttendanceRecordRequest;
        $rules = $request->rules();

        $data = $value;

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->passes());
    }

    public static function passesUpdateValidateValueProvider(): array
    {
        $commentMax = str_repeat('a', 255);

        return [
            '勤怠修正のバリデーションで出勤、退勤、休憩、備考を入力し、正常に勤怠修正がされる_備考は255文字' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => $commentMax,
                ],
            ],
        ];
    }

    /**
     * @dataProvider failsUpdateValidateValueProvider
     *
     * UpdateAttendanceRecordRequestの異常系テスト
     *
     * @param  array  $value  データプロバイダーから受け取るテストデータ
     */
    public function test_update_validate_fails(array $value): void
    {
        $request = new UpdateAttendanceRecordRequest;
        $rules = $request->rules();

        $data = $value;

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
    }

    public static function failsUpdateValidateValueProvider(): array
    {
        $commentMaxOver = str_repeat('a', 256);

        return [
            '勤怠修正のバリデーションで出勤に入力せず、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで出勤に文字列を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => 'test',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで退勤時刻より後の出勤時刻を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '19:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで退勤に文字列を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => 'test',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで出勤時刻より前の退勤時刻を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '08:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで休憩入に配列以外を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => 'test',
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで休憩入に文字列の入った配列を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['test', 'test'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで退勤時刻より後の休憩入時刻の入った配列を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['19:00:00', '20:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで出勤時刻より前の休憩入時刻の入った配列を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['07:00:00', '08:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで休憩戻に配列以外を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => 'test',
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで休憩戻に文字列の入った配列を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['test', 'test'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで退勤時刻より後の休憩戻時刻の入った配列を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['19:00:00', '20:00:00'],
                    'comment' => 'test',
                ],
            ],

            '勤怠修正のバリデーションで備考に入力せず、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => '',
                ],
            ],

            '勤怠修正のバリデーションで備考に数値を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => 1,
                ],
            ],

            // 境界値テスト
            '勤怠修正のバリデーションで備考に256文字の文字列を入力し、バリデーションエラーとなる' => [
                [
                    'new_clock_in' => '09:00:00',
                    'new_clock_out' => '18:00:00',
                    'new_break_in' => ['12:00:00', '13:00:00'],
                    'new_break_out' => ['12:30:00', '13:30:00'],
                    'comment' => $commentMaxOver,
                ],
            ],
        ];
    }
}
