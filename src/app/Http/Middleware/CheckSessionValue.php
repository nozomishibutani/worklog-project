<?php

namespace App\Http\Middleware;

use App\Enums\LoginForm;
use Closure;
use Illuminate\Http\Request;

class CheckSessionValue
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        /***********************************************************************
            管理者権限を持つ者が片方でログインしそのままもう一方の画面に認証なしで
            ログインするのを防ぐため、ログインに対するsessionを持っているか確認する
        ************************************************************************/

        //dd('here');

        if ($request->routeIs('admin.*')) {
            if (session('login_form') !== LoginForm::ADMIN->value || session('user_id') !== $request->user()->id) {
                return redirect()->route('admin.login')
                                    ->with('alert', '管理者権限が必要です')
                                    ->with('alert-type', 'alert-error');
            }
        } else {
            if (session('login_form') !== LoginForm::GENERAL->value || session('user_id') !== $request->user()->id) {

                return redirect()->route('login');
            }
        }
        return $next($request);
    }
}
