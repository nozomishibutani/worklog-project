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
->middleware('guest:admin')
    ->group(function () {

        Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])
            ->name('admin.login');

        // 期待通りに SetGuard が動かないので route に明示する
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

    });


// =====================
// user
// =====================

Route::middleware(['web', SetGuard::class])
    ->group(function () {
        Route::get('/attendance', [UserController::class, 'index'])
            ->name('user.index');

    });

Route::get('/', function () {
    return view('welcome');
});
