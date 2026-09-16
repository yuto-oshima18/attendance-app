<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/v1/attendance-records', [AttendanceRecordController::class, 'index'])->name('api.attendance-records.index');
Route::get('/v1/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'show'])->name('api.attendance-records.show');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/attendance-records', [AttendanceRecordController::class, 'store'])->name('api.attendance-records.store');
    Route::put('/v1/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update'])->name('api.attendance-records.update');
    Route::delete('/v1/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'destroy'])->name('api.attendance-records.destroy');
});
