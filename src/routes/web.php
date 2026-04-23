<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\VerificationController;
use App\Http\Middleware\CheckSessionValue;
use App\Http\Middleware\CheckAdmin;

// =====================
// admin
// =====================
Route::prefix('admin')
    ->group(function () {
        Route::get('/login', function () {
            return view('auth.admin-login');
        })->name('admin.login');
    });

Route::prefix('admin')
    ->middleware([
        'auth',
        CheckAdmin::class,
        CheckSessionValue::class,
    ])
    ->group(function () {
        Route::get('/attendance/list', [AdminController::class, 'index'])
                    ->name('admin.index');
        Route::get('/attendance/{id?}', [AdminController::class, 'show'])
                    ->name('admin.show');
        Route::get('/staff/list', [AdminController::class, 'userIndex'])
                    ->name('admin.user.index');
        Route::get('/attendance/staff/{id}', [AdminController::class, 'userMonthlyIndex'])
                    ->name('admin.monthly.index');
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminController::class, 'showForApproval'])
                    ->name('admin.approval.show');
        Route::post('/stamp_correction_request/approve/', [AdminController::class, 'approve'])
                    ->name('admin.approve');
        Route::post('/attendance/update', [AdminController::class, 'update'])
                    ->name('admin.update');
        Route::get('/attendance/export/{user_id}/{date}', [AdminController::class, 'export'])
                    ->name('admin.export');
    });

// =====================
// 共通
// =====================
Route::middleware([
        'auth',
        'verified',
    ])
    ->group(function () {
        Route::get('/stamp_correction_request/list', [CommonController::class, 'applicationIndex'])
            ->name('application.index');
    });

// =====================
// user
// =====================
Route::middleware([
        'auth',
        'verified',
        CheckSessionValue::class,
    ])
    ->group(function () {
        Route::get('/attendance', [UserController::class, 'index'])
            ->name('index');
        Route::post('/attendance', [UserController::class, 'logAttendance'])
            ->name('log');
        Route::get('/attendance/list', [UserController::class, 'monthlyIndex'])
            ->name('monthly.index');
        Route::get('/attendance/detail/{id?}', [UserController::class, 'show'])
            ->name('show');
        Route::post('/attendance/update', [UserController::class, 'update'])
            ->name('update');
    });

// メール認証誘導画面
Route::get('/verify/notice', [VerificationController::class, 'notice'])->name('verification.notice');
// メール認証画面
Route::get('/verify/email/confirm', [VerificationController::class, 'confirm'])->name('verification.confirm');
Route::middleware(['signed'])->group(function () {
    // メール認証
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
});
Route::middleware(['throttle:3,1'])->group(function () {
    // 認証メール再送
    Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.send');
});
