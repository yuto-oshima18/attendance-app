<?php

use App\Http\Controllers\AdminApplicationRequestController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\ApplicationRequestController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| /
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');

});

/*
|--------------------------------------------------------------------------
| 一般ユーザーログイン
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('user.user-login');
    })->name('login');

    Route::post('/login', [
        AuthenticatedSessionController::class,
        'store',
    ])->name('login.store');
});

/*
|--------------------------------------------------------------------------
| 一般ユーザー作成
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', function () {
        return view('user.register');
    })->name('register');

    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

/*
|--------------------------------------------------------------------------
| メール認証
|--------------------------------------------------------------------------
*/
// 認証催促画面
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// 認証ボタン押下後
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect(route('attendance.create'));
})->middleware(['auth', 'signed'])->name('verification.verify');

// 認証メール送信
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', '認証メールを送信しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

/*
|--------------------------------------------------------------------------
| 管理者ログイン
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', function () {
        return view('admin.admin-login');
    })->name('admin.login');

    Route::post('/admin/login', [
        AuthenticatedSessionController::class,
        'store',
    ])->name('admin.login.store');
});

/*
|--------------------------------------------------------------------------
| ログアウト
|--------------------------------------------------------------------------
*/
Route::post('/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])->middleware(['auth', 'verified'])->name('logout');

Route::post('/admin/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])->middleware(['auth', 'verified'])->name('admin.logout');

/*
|--------------------------------------------------------------------------
| 一般ユーザー画面
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{id}', [ApplicationRequestController::class, 'store'])->name('app.attendance.show');
    Route::get('/stamp_correction_request/list', function () {
        if (auth()->user()->admin_status) {
            return app(AdminApplicationRequestController::class)->index();
        }

        return app(ApplicationRequestController::class)->index();
    })->name('stamp.correction.request.index');
});

/*
|--------------------------------------------------------------------------
| 管理者画面
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin', 'verified'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::get('/admin/attendance/staff/{id}', [AdminStaffController::class, 'show'])->name('admin.attendance.staff.show');
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
    Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])->name('admin.staff.index');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationRequestController::class, 'show'])
        ->name('stamp.correction.request.approve.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationRequestController::class, 'update'])
        ->name('stamp.correction.request.approve.update');
    Route::post('/export', [AdminStaffController::class, 'export'])->name('export');

});
