<?php

use App\Http\Controllers\AdminApplicationRequestController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\ApplicationRequestController;
use App\Http\Controllers\AttendanceController;
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
])->middleware('auth')->name('logout');

Route::post('/admin/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])->middleware('auth')->name('admin.logout');

/*
|--------------------------------------------------------------------------
| 一般ユーザー画面
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{id}', [ApplicationRequestController::class, 'store'])->name('app.attendance.show');
    Route::get('stamp_correction_request/list', function () {
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
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::get('admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
    Route::post('admin/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
    Route::get('/admin/staff/list', [AdminStaffController::class, 'index'])->name('admin.staff.index');
    Route::get('/admin/attendance/staff/{id}', [AdminStaffController::class, 'show'])->name('admin.attendance.staff.show');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationRequestController::class, 'show'])
        ->name('stamp.correction.request.approve.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationRequestController::class, 'update'])
        ->name('stamp.correction.request.approve.update');
});
