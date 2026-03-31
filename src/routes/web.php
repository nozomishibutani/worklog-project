<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\AdminAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Middleware\SetGuard;


// =====================
// admin
// =====================
Route::prefix('admin')
    ->group(function () {

        Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])
            ->name('admin.login');

        // SetGuard が動かないので route に明示する
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
                        ->name('admin.login');

    });

Route::prefix('admin')
    ->middleware('auth:admin')
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
        Route::get('/{id}/{date}', [AdminController::class, 'setSession'])
                    ->where('date', '\d{8}')
                    ->name('admin.session');
        Route::post('/attendance/update', [AdminController::class, 'update'])
                            ->name('admin.update');
    });

// =====================
// userとadmin 共有
// =====================

Route::middleware('auth:web')
->group(function () {
    Route::get('/stamp_correction_request/list', [UserController::class, 'applicationIndex'])
                                        ->name('application.index');
});

Route::middleware('auth:admin')
->group(function () {
    Route::get('/stamp_correction_request/list', [AdminController::class, 'applicationIndex'])
                                        ->name('application.index');
});




// =====================
// user
// =====================

Route::middleware(['web', SetGuard::class])
    ->group(function () {
        Route::get('/attendance', [UserController::class, 'index'])
            ->name('user.index');

    });
