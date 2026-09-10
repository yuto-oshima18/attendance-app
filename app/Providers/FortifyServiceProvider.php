<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\LoginResponse as CustomLoginResponse;
use App\Http\Responses\LogoutResponse as CustomLogoutResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fortify標準のルートを無効化
        Fortify::ignoreRoutes();

        // ログイン後のレスポンス
        $this->app->singleton(
            LoginResponse::class,
            CustomLoginResponse::class
        );

        // ログアウト後のレスポンス
        $this->app->singleton(
            LogoutResponse::class,
            CustomLogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::updateUserProfileInformationUsing(
            UpdateUserProfileInformation::class
        );

        Fortify::updateUserPasswordsUsing(
            UpdateUserPassword::class
        );

        Fortify::resetUserPasswordsUsing(
            ResetUserPassword::class
        );

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where(
                Fortify::username(),
                $request->input(Fortify::username())
            )->first();

            if (
                $user &&
                Hash::check($request->password, $user->password)
            ) {
                // 管理者ログインの場合
                if ($request->is('admin/login')) {
                    return $user->admin_status ? $user : null;
                }

                // 一般ログインの場合
                // 一般ユーザー・管理者ともにログイン可能
                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower(
                    $request->input(Fortify::username())
                ).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        // 一般ユーザーログイン画面
        Fortify::loginView(function () {
            return view('user.user-login');
        });

        // ユーザー登録画面
        Fortify::registerView(function () {
            return view('user.register');
        });
    }
}
