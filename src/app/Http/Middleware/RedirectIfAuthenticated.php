<?php

namespace App\Http\Middleware;

use App\Enums\LoginForm;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated as Middleware;
use Illuminate\Http\Request;

class RedirectIfAuthenticated extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        /***********************************************************************
            一般・管理画面どちらかでログイン済みで、ログイン画面に遷移した場合
            管理画面はguestが適用されないので、アクセスすると画面は表示される
        ************************************************************************/

        // 管理画面にログイン済みで、再度管理画面にアクセス → 問題なし
        if ($request->form === LoginForm::ADMIN->value) {
            if (session('login_form') === LoginForm::ADMIN->value) {
                return route('admin.index');
            }
        }

        // 一般でログイン済みで、管理画面のログイン試行 → 管理画面のログインに遷移し、ログアウトを促す
        if (session('login_form') === LoginForm::GENERAL->value) {
            if ($request->form === LoginForm::ADMIN->value) {
                return route('admin.login');
            }
        }

        // 一般でログイン済みで、一般ログイン画面にアクセス → 問題なし
        // 管理画面にログイン済みで、一般ログイン画面にアクセス → ログイン済みの場合は一般ログイン画面を表示できないのでCheckSessionValueで捕まえる
        return route('index');
    }
}
