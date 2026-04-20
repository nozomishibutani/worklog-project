<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use App\Http\Responses\LoginResponse as CustomLoginResponse;
use App\Http\Responses\LogoutResponse as CustomLogoutResponse;
use App\Http\Responses\RegisterResponse as CustomRegisterResponse;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use App\Http\Requests\LoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RegisterResponse::class, CustomRegisterResponse::class);
        $this->app->singleton(LoginResponse::class, CustomLoginResponse::class);
        $this->app->singleton(LogoutResponse::class, CustomLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function ($request) {
            return $request->is('admin/login')
                ? view('auth.admin-login')
                : view('auth.login');
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(10)->by($email . $request->ip());
        });

        Fortify::verifyEmailView('auth/notice');

        //デフォルトのログイン機能にあるフォームリクエストを自作のものに代替するため、サービスコンテナにバインド
        app()->bind(FortifyLoginRequest::class, LoginRequest::class);
    }
}
