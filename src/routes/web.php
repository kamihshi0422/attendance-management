<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserAttendanceController;
use App\Http\Controllers\UserApplicationController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminApplicationController;

Route::get('/register', [AuthController::class, 'showRegister'])->name('register.show');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.show');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/email/verify', [AuthController::class, 'verificationNotice'])
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [UserAttendanceController::class, 'showAttendance'])->name('attendance.show');
    Route::post('/attendance/clock-in', [UserAttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/break-start', [UserAttendanceController::class, 'startBreak'])->name('attendance.break.start');
    Route::post('/attendance/break-end', [UserAttendanceController::class, 'endBreak'])->name('attendance.break.end');
    Route::post('/attendance/clock-out', [UserAttendanceController::class, 'clockOut'])->name('attendance.clockOut');

    Route::get('/attendance/list/detail/{id}', [UserAttendanceController::class, 'showAttendanceDetail'])->name('attendanceDetail.show');
    Route::post('/attendance/list/detail/{attendance}', [UserApplicationController::class, 'submitCorrection'])->name('attendance.submitCorrection');
    Route::post('/attendance/create', [UserApplicationController::class, 'createForDate'])->name('attendance.createForDate');
    Route::get('/attendance/list', [UserAttendanceController::class, 'showAttendanceList'])->name('attendanceList.show');

    Route::get('/stamp_correction_request/list', [UserApplicationController::class, 'showApplicationList'])->name('applicationList.show');
});

Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('show.admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('show.admin.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'showAdminAttendanceList'])->name('admin.attendanceList.show');
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'showAdminDetail'])->name('admin.attendanceDetail.show');
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'submitAdminCorrection'])->name('admin.submitCorrection');

    Route::get('/admin/staff/list', [AdminStaffController::class, 'showStaffList'])->name('staffList.show');
    Route::get('/admin/attendance/staff/{id}', [AdminStaffController::class, 'showStaffAttendance'])->name('staffAttendance.show');
    Route::get('/staff/{id}/attendance/csv', [AdminStaffController::class, 'exportCsv'])->name('staffAttendance.csv');

    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationController::class, 'showApplicationApproval'])->name('applicationApproval.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminApplicationController::class, 'applicationApproval'])->name('application.approve');
});
