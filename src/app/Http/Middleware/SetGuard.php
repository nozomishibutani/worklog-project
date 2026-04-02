<?php

namespace App\Http\Middleware;

use Closure;

class SetGuard
{
    public function handle($request, Closure $next)
    {
        if ($request->is('admin/login') || $request->is('admin/login/*')) {
            config(['fortify.guard' => 'admin']);
        } //else {
        //     config(['fortify.guard' => 'web']);
        // }
        return $next($request);
    }
}