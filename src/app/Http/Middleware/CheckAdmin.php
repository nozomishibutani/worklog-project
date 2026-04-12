<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return redirect()->route('admin.login')
                            ->with('alert', '管理者権限が必要です')
                            ->with('alert-type', 'alert-error');
        }
        return $next($request);
    }
}
