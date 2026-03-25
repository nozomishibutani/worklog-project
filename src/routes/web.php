<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;
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
Route::post('/attendance/list', [AdminController::class, 'changeDate'])
            ->name('admin.change_date');
Route::get('/attendance/{id}', [AdminController::class, 'show'])
            ->name('admin.show');
Route::get('/admin/staff/list', [AdminController::class, 'userIndex'])
            ->name('admin.user.index');
Route::get('/admin/attendance/staff/{id}', [AdminController::class, 'userMonthlyIndex'])
            ->name('admin.monthly.index');

    });


// =====================
// user
// =====================

Route::middleware(['web', SetGuard::class])
    ->group(function () {
        Route::get('/attendance', [UserController::class, 'index'])
            ->name('user.index');

    });
