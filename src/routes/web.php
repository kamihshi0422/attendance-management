<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserAttendanceController;
use App\Http\Controllers\UserApplicationController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminApplicationController;

/*
|--------------------------------------------------------------------------
| 認証
|--------------------------------------------------------------------------
*/
Route::get('/register', [AuthController::class, 'showRegister'])->name('register.show');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.show');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// メール認証
Route::get('/email/verify', [AuthController::class, 'verificationNotice'])
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');


/*
|--------------------------------------------------------------------------
| 一般ユーザー
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // 今日の勤怠
    Route::get('/attendance', [UserAttendanceController::class, 'showAttendance'])
        ->name('attendance.show');

    Route::post('/attendance/clock-in', [UserAttendanceController::class, 'clockIn'])
        ->name('attendance.clockIn');

    Route::post('/attendance/break-start', [UserAttendanceController::class, 'startBreak'])
        ->name('attendance.break.start');

    Route::post('/attendance/break-end', [UserAttendanceController::class, 'endBreak'])
        ->name('attendance.break.end');

    Route::post('/attendance/clock-out', [UserAttendanceController::class, 'clockOut'])
        ->name('attendance.clockOut');

    // 勤怠詳細
    Route::get('/attendance/list/detail/{id}', [UserAttendanceController::class, 'showAttendanceDetail'])
        ->name('attendanceDetail.show');

    // 修正申請
    Route::post('/attendance/list/detail/{attendance}', [UserApplicationController::class, 'submitCorrection'])
        ->name('attendance.submitCorrection');

    // 勤怠新規作成
    Route::post('/attendance/create', [UserApplicationController::class, 'createForDate'])
        ->name('attendance.createForDate');

    // 勤怠一覧
    Route::get('/attendance/list', [UserAttendanceController::class, 'showAttendanceList'])
        ->name('attendanceList.show');

    // 申請一覧
    Route::get('/stamp_correction_request/list', [UserApplicationController::class, 'showApplicationList'])
        ->name('applicationList.show');
});


/*
|--------------------------------------------------------------------------
| 管理者ログイン
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('show.admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('show.admin.store');


/*
|--------------------------------------------------------------------------
| 管理者
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // 日別勤怠一覧
    Route::get('/admin/attendance/list/{year?}/{month?}', [AdminAttendanceController::class, 'showAdminAttendanceList'])
        ->name('admin.attendanceList.show');

    // 勤怠詳細
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'showAdminDetail'])
        ->name('admin.attendanceDetail.show');

    // 管理者勤怠修正
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'submitAdminCorrection'])
        ->name('admin.submitCorrection');

    // スタッフ一覧
    Route::get('/admin/staff/list', [AdminAttendanceController::class, 'showStaffList'])
        ->name('staffList.show');

    // スタッフ別勤怠
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'showStaffAttendance'])
        ->name('staffAttendance.show');

    // CSV 出力
    Route::get('/staff/{id}/attendance/csv', [AdminAttendanceController::class, 'exportCsv'])
        ->name('staffAttendance.csv');

    // 修正申請承認画面
    Route::get(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminApplicationController::class, 'showApplicationApproval']
    )->name('applicationApproval.show');

    // 修正申請承認処理
    Route::post(
        '/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminApplicationController::class, 'applicationApproval']
    )->name('application.approve');
});
