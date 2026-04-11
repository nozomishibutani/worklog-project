<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\AdminAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Middleware\CheckSessionValue;
use App\Http\Middleware\CheckAdmin; //->middleware(CheckAdmin::class)

// =====================
// admin
// =====================
Route::prefix('admin')
    ->group(function () {
        Route::get('/login', function () {
            // if (session('role') === Role::ADMIN->value) {
            //     return redirect()->route('admin.index');
            // }
            return view('auth.admin-login');
        })->name('admin.login');

        // SetGuard が動かないので route に明示する
        //Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        //                ->name('admin.login');
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
        Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
                    ->name('admin.logout');
        Route::get('/attendance/{id?}', [AdminController::class, 'show'])
                    ->name('admin.show');
        Route::get('/staff/list', [AdminController::class, 'userIndex'])
                    ->name('admin.user.index');
        Route::get('/attendance/staff/{id}', [AdminController::class, 'userMonthlyIndex'])
                    ->name('admin.monthly.index');

        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminController::class, 'showForApproval'])
                            ->name('admin.approval.show');
        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminController::class, 'approve'])
                        ->name('admin.approve');
        Route::post('/attendance/update', [AdminController::class, 'update'])
                                    ->name('admin.update');
        Route::get('/attendance/export/{user_id}/{date}', [AdminController::class, 'export'])
        ->name('admin.export');

    });

// =====================
// userとadmin 共有
// =====================
Route::middleware([
        'auth',
        //CheckSessionValue::class,
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
        CheckSessionValue::class,
    ])->
    group(function () {
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

Route::get('/', function () {
    return redirect()->route('login');
});
