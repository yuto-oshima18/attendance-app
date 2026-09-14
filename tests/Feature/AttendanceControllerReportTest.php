<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceControllerReportTest extends TestCase
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
     * 項目：マイ勤怠レポート機能
     *
     * 1. 認証ユーザーで勤怠データを複数日作成
     * 2. GET /attendance/report を実行
     */
    public function 認証ユーザーの統計情報が正しく計算される(): void
    {
        // 応用要件にて過去6ヶ月分の勤怠情報をシーダーで登録したユーザー
        $user = User::findOrFail(1);

        $nowMonth = now()->format('Y/m');
        $aMonthAgo = now()->subMonth()->format('Y/m');
        $twoMonthsAgo = now()->subMonths(2)->format('Y/m');
        $threeMonthsAgo = now()->subMonths(3)->format('Y/m');
        $fourMonthsAgo = now()->subMonths(4)->format('Y/m');
        $fiveMonthsAgo = now()->subMonths(5)->format('Y/m');

        $response = $this->actingAs($user)->get(route('attendance.report'));

        $response->assertStatus(200);

        $response->assertSee('744h 0m');
        $response->assertSee('10h 0m');
        $response->assertSee('8h 5m');
        $response->assertSee($nowMonth);
        $response->assertSee($aMonthAgo);
        $response->assertSee($twoMonthsAgo);
        $response->assertSee($threeMonthsAgo);
        $response->assertSee($fourMonthsAgo);
        $response->assertSee($fiveMonthsAgo);
        $response->assertSee('120h 0m');
        $response->assertSee('0h 0m');
        $response->assertSee('120h 0m');
        $response->assertSee('10h 0m');
        $response->assertSee('2 回');
        $response->assertSee('1 回');
        $response->assertSee('1 日');
    }

    /**
     * @test
     * 項目：マイ勤怠レポート機能
     *
     * 1. 勤怠データのないユーザーで認証
     * 2. GET /attendance/report を実行
     */
    public function 勤怠記録がないユーザーで安全に処理される(): void
    {
        // 応用要件にて過去6ヶ月分の勤怠情報をシーダーで登録したユーザー
        $user = User::factory()->create([
            'attendance_status' => '勤務外',
            'email_verified_at' => now(),
        ]);

        $nowMonth = now()->format('Y/m');
        $aMonthAgo = now()->subMonth()->format('Y/m');
        $twoMonthsAgo = now()->subMonths(2)->format('Y/m');
        $threeMonthsAgo = now()->subMonths(3)->format('Y/m');
        $fourMonthsAgo = now()->subMonths(4)->format('Y/m');
        $fiveMonthsAgo = now()->subMonths(5)->format('Y/m');

        $response = $this->actingAs($user)->get(route('attendance.report'));

        $response->assertStatus(200);

        $response->assertSee('0h 0m');
        $response->assertSee($nowMonth);
        $response->assertSee($aMonthAgo);
        $response->assertSee($twoMonthsAgo);
        $response->assertSee($threeMonthsAgo);
        $response->assertSee($fourMonthsAgo);
        $response->assertSee($fiveMonthsAgo);
        $response->assertSee('0 回');
        $response->assertSee('0 日');
    }
}
