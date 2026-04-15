<?php

namespace App\Http\Middleware;

use App\Enums\LoginForm;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if ($request->routeIs('admin.*')) {
            return route('admin.login');
        } else {
            return route('login');
        }
    }
}
