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
            遷移するのを防ぐため、ログインに対するsessionを持っているか確認する
        ************************************************************************/

        // 一般にログイン済みで管理画面にアクセス
        if ($request->routeIs('admin.*')) {
            if (session('login_form') !== LoginForm::ADMIN->value || session('user_id') !== $request->user()->id) {
                return redirect()->route('admin.login');
            }
        } else {
            if (session('login_form') !== LoginForm::GENERAL->value || session('user_id') !== $request->user()->id) {
                // 管理画面でログイン済みでユーザー画面にアクセス
                return redirect()->route('admin.index')
                                    ->with('alert', '現在は管理者としてログインしています。ユーザー画面を利用するには一度ログアウトしてください。')
                                    ->with('alert-type', 'alert--error');

            }
        }
        return $next($request);
    }
}
