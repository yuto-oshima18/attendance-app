<?php

use App\Http\Controllers\ApplicationRequestController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/login', function () {
    return view('admin.admin-login');
})->name('admin.login');

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{id}', [ApplicationRequestController::class, 'store'])->name('app.attendance.show');
    Route::get('/stamp_correction_request/list', [ApplicationRequestController::class, 'index'])->name('stamp_correction_request.index');

    // 仮に設定(ブレード内URLが違うため)
    Route::get('/application/{id}', [AttendanceController::class, 'show'])->name('application.show');
});
