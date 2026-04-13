<?php

namespace App\Http\Middleware;

use App\Enums\LoginForm;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        dd('here');
        // デフォルトのmiddleware authのリダイレクト先を変更する
        if (session('login_form') === LoginForm::ADMIN->value) {
            return route('admin.login');
        }
        return route('login');
    }
}
