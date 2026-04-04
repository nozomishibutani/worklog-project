<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\AdminAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Middleware\SetGuard;
use App\Http\Controllers\StampCorrectionRequestController;

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
        Route::post('/attendance/update', [AdminController::class, 'update'])
                            ->name('admin.update');
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminController::class, 'showForApproval'])
                            ->name('admin.approval.show');
        Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminController::class, 'approve'])
                        ->name('admin.approve');
    });

// =====================
// userとadmin 共有
// =====================
Route::middleware('auth:web,admin') // 両方明示することでアクセス可能
->group(function () {
    Route::get('/stamp_correction_request/list', [CommonController::class, 'applicationIndex'])
                                        ->name('application.index');
});


// Route::get('/stamp_correction_request/list', function () {

//     if (auth('admin')->check()) {
//         return [AdminController::class, 'applicationIndex'];
//     }

//     if (auth('web')->check()) {
//         //[UserController::class, 'applicationIndex'];
//         return 'Hello Index';
//     }
// })->middleware('auth:admin,web')->name('application.index');




// =====================
// user
// =====================

Route::middleware('auth:web')
        ->group(function () {
            Route::get('/attendance', [UserController::class, 'index'])
                ->name('index');
            Route::post('/attendance', [UserController::class, 'register'])
                ->name('register');
            Route::get('/attendance/list', [UserController::class, 'monthlyIndex'])
                ->name('monthly.index');
            Route::get('/attendance/detail/{id?}', [UserController::class, 'show'])
                ->name('show');

        });

Route::get('/', function () {
    return redirect()->route('index');
});



