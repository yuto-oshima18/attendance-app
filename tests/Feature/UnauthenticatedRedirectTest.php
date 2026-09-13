<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnauthenticatedRedirectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態で出勤登録画面（一般ユーザー）へアクセス
     */
    public function 出勤登録画面（一般ユーザー）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('attendance.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態で勤怠一覧画面（一般ユーザー）へアクセス
     */
    public function 勤怠一覧画面（一般ユーザー）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('attendance.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態で勤怠詳細画面（一般ユーザー）へアクセス
     */
    public function 勤怠詳細画面（一般ユーザー）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('attendance.show', 1));

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態で申請一覧画面（一般ユーザー）へアクセス
     */
    public function 申請一覧画面（一般ユーザー）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('stamp.correction.request.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態で勤怠一覧画面（管理者）へアクセス
     */
    public function 勤怠一覧画面（管理者）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('admin.attendance.index'));

        $response->assertRedirect(route('admin.login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態で勤怠詳細画面（管理者）へアクセス
     */
    public function 勤怠詳細画面（管理者）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('admin.attendance.show', 1));

        $response->assertRedirect(route('admin.login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態でスタッフ一覧画面（管理者）へアクセス
     */
    public function スタッフ一覧画面（管理者）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('admin.staff.index'));

        $response->assertRedirect(route('admin.login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態でスタッフ別勤怠一覧画面（管理者）へアクセス
     */
    public function スタッフ別勤怠一覧画面（管理者）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('admin.attendance.staff.show', 1));

        $response->assertRedirect(route('admin.login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態で申請一覧画面（管理者）へアクセス
     */
    public function 申請一覧画面（管理者）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('stamp.correction.request.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態で修正申請承認画面（管理者）へアクセス
     */
    public function 修正申請承認画面（管理者）へアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('stamp.correction.request.approve.show', 1));

        $response->assertRedirect(route('admin.login'));
    }

    /**
     * @test
     * 項目：未ログイン時の追加分テスト
     *
     * 1. 未ログイン状態でCSV出力のURLアクセス
     */
    public function cs_v出力の_ur_lアクセス時、未認証ユーザはログイン画面にリダイレクトされる(): void
    {
        $User = User::factory()->create([
            'attendance_status' => '勤務外',
        ]);

        $response = $this->post(route('export').'?'.http_build_query([
            'user_id' => $User->id,
            'year_month' => now()->format('Y-m'),
        ]));

        $response->assertRedirect(route('admin.login'));
    }
}
