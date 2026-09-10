<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test
     * 項目：認証機能（一般ユーザー）
     *
     * 1. 名前以外のユーザー情報を入力する
     * 2. 会員登録の処理を行う
     */
    public function 名前が未入力の場合、バリデーションメッセージが表示される(): void
    {
        $data = [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->post(route('register.store'), $data);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * @test
     * 項目：認証機能（一般ユーザー）
     *
     * 1. メールアドレス以外のユーザー情報を入力する
     * 2. 会員登録の処理を行う
     */
    public function メールアドレスが未入力の場合、バリデーションメッセージが表示される(): void
    {
        $data = [
            'name' => 'test',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->post(route('register.store'), $data);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * 項目：認証機能（一般ユーザー）
     *
     * 1. パスワードを8文字未満にし、ユーザー情報を入力する
     * 2. 会員登録の処理を行う
     */
    public function パスワードが8文字未満の場合、バリデーションメッセージが表示される(): void
    {
        $data = [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ];

        $response = $this->post(route('register.store'), $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * @test
     * 項目：認証機能（一般ユーザー）
     *
     * 1. 確認用のパスワードとパスワードを一致させず、ユーザー情報を入力する
     * 2. 会員登録の処理を行う
     */
    public function パスワードが一致しない場合、バリデーションメッセージが表示される(): void
    {
        $data = [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'P@ssw0rd',
        ];

        $response = $this->post(route('register.store'), $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /**
     * @test
     * 項目：認証機能（一般ユーザー）
     *
     * 1. パスワード以外のユーザー情報を入力する
     * 2. 会員登録の処理を行う
     */
    public function パスワードが未入力の場合、バリデーションメッセージが表示される(): void
    {
        $data = [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => 'password',
        ];

        $response = $this->post(route('register.store'), $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * 項目：認証機能（一般ユーザー）
     *
     * 1. ユーザー情報を入力する
     * 2. 会員登録の処理を行う
     */
    public function フォームに内容が入力されていた場合、データが正常に保存される(): void
    {
        $data = [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->post(route('register.store'), $data);

        $this->assertDatabaseHas('users', [
            'name' => 'test',
            'email' => 'test@example.com',
        ]);
    }

    /**
     * @test
     * 項目：ログイン認証機能（一般ユーザー）
     *
     * 1. ユーザーを登録する
     * 2. メールアドレス以外のユーザー情報を入力する
     * 3. ログインの処理を行う
     */
    public function メールアドレスが未入力の場合、バリデーションメッセージが表示される_一般ログイン(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $this->post(route('register.store'), $userData);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
        ]);

        $this->post(route('logout'));

        $loginData = [
            'email' => '',
            'password' => $password,
        ];
        
        $response = $this->post(route('login.store'), $loginData);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * 項目：ログイン認証機能（一般ユーザー）
     *
     * 1. ユーザーを登録する
     * 2. パスワード以外のユーザー情報を入力する
     * 3. ログインの処理を行う
     */
    public function パスワードが未入力の場合、バリデーションメッセージが表示される_一般ログイン(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $this->post(route('register.store'), $userData);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
        ]);

        $this->post(route('logout'));

        $loginData = [
            'email' => $email,
            'password' => '',
        ];
        
        $response = $this->post(route('login.store'), $loginData);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * 項目：ログイン認証機能（一般ユーザー）
     *
     * 1. ユーザーを登録する
     * 2. 誤ったメールアドレスのユーザー情報を入力する
     * 3. ログインの処理を行う
     */
    public function 登録内容と一致しない場合、バリデーションメッセージが表示される_一般ログイン(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $this->post(route('register.store'), $userData);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
        ]);

        $this->post(route('logout'));

        $loginData = [
            'email' => 'test@test',
            'password' => $password,
        ];
        
        $response = $this->post(route('login.store'), $loginData);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * @test
     * 項目：ログイン認証機能（管理者）
     *
     * 1. ユーザーを登録する
     * 2. メールアドレス以外のユーザー情報を入力する
     * 3. ログインの処理を行う
     */
    public function メールアドレスが未入力の場合、バリデーションメッセージが表示される_管理者ログイン(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'admin_status' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
            'admin_status' => true,
        ]);

        $loginData = [
            'email' => '',
            'password' => $password,
        ];
        
        $response = $this->post(route('admin.login.store'), $loginData);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * 項目：ログイン認証機能（管理者）
     *
     * 1. ユーザーを登録する
     * 2. パスワード以外のユーザー情報を入力する
     * 3. ログインの処理を行う
     */
    public function パスワードが未入力の場合、バリデーションメッセージが表示される_管理者ログイン(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'admin_status' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
            'admin_status' => true,
        ]);

        $loginData = [
            'email' => $email,
            'password' => '',
        ];
        
        $response = $this->post(route('admin.login.store'), $loginData);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * 項目：ログイン認証機能（管理者）
     *
     * 1. ユーザーを登録する
     * 2. 誤ったメールアドレスのユーザー情報を入力する
     * 3. ログインの処理を行う
     */
    public function 登録内容と一致しない場合、バリデーションメッセージが表示される_管理者ログイン(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'admin_status' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
            'admin_status' => true,
        ]);

        $loginData = [
            'email' => 'test@test',
            'password' => $password,
        ];
        
        $response = $this->post(route('admin.login.store'), $loginData);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * @test
     * 項目：認証機能（一般ユーザー）:追加分テスト
     *
     * 1. 会員登録画面（一般ユーザー）を表示する
     */
    public function ユーザ登録ページを表示できる(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    /**
     * @test
     * 項目：認証機能（一般ユーザー）:追加分テスト
     *
     * 1. ログイン画面（一般ユーザー）より正常にログインする。
     * 2. ユーザ登録ページにアクセスする。
     */
    public function 認証済みユーザーはユーザ登録ページにアクセスするとリダイレクトされる(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
        ]);

        $loginData = [
            'email' => $email,
            'password' => $password,
        ];
        
        $response = $this->post(route('login.store'), $loginData);

        $response->assertRedirect(route('attendance.create'));
        $this->assertAuthenticatedAs($user);

        $response = $this->get(route('register'));
        $response->assertRedirect(route('attendance.create'));
    }

    /**
     * @test
     * 項目：ログイン認証機能（一般ユーザー）:追加分テスト
     *
     * 1. ログイン画面（一般ユーザー）を表示する
     */
    public function 一般ログインページを表示できる(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    /**
     * @test
     * 項目：ログイン認証機能（一般ユーザー）:追加分テスト
     *
     * 1. ログイン画面（一般ユーザー）より正常にログインする。
     */
    public function 一般ログインページにて正しい認証情報でログインできる(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
        ]);

        $loginData = [
            'email' => $email,
            'password' => $password,
        ];
        
        $response = $this->post(route('login.store'), $loginData);

        $response->assertRedirect(route('attendance.create'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     * 項目：ログイン認証機能（一般ユーザー）:追加分テスト
     *
     * 1. ログイン画面（一般ユーザー）より正常にログインする。
     * 2. 正常にログアウトする。 
     */
    public function 一般用ページからログアウトできる(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
        ]);

        $loginData = [
            'email' => $email,
            'password' => $password,
        ];
        
        $response = $this->post(route('login.store'), $loginData);

        $response->assertRedirect(route('attendance.create'));
        $this->assertAuthenticatedAs($user);

        $response = $this->post(route('logout'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * 項目：ログイン認証機能（一般ユーザー）:追加分テスト
     *
     * 1. ログイン画面（一般ユーザー）より正常にログインする。
     * 2. 一般ログインページにアクセスする。
     */
    public function 認証済みユーザーは一般ログインページにアクセスするとリダイレクトされる(): void
    {
        $name = 'test';
        $email = 'test@example.com';
        $password = 'password';

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
        ]);

        $loginData = [
            'email' => $email,
            'password' => $password,
        ];
        
        $response = $this->post(route('login.store'), $loginData);

        $response->assertRedirect(route('attendance.create'));
        $this->assertAuthenticatedAs($user);

        $response = $this->get(route('login'));
        $response->assertRedirect(route('attendance.create'));
    }

    /**
     * @test
     * 項目：ログイン認証機能（管理者）:追加分テスト
     *
     * 1. ログイン画面（管理者）を表示する
     */
    public function 管理者ログインページを表示できる(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    
}
