<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use App\Enums\Role;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        // デフォルトのmiddleware authのリダイレクト先を変更する
        if (session('role') === Role::ADMIN->value) {
            return route('admin.login');
        }
        return route('login');
    }
}
