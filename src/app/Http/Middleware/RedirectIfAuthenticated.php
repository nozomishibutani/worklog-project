<?php

namespace App\Http\Middleware;

use App\Enums\LoginForm;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated as Middleware;
use Illuminate\Http\Request;

class RedirectIfAuthenticated extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // if ($request->getRequestUri() === "/login") {
        //     if (session('login_form') === LoginForm::ADMIN->value) {

        //     // どっちからログインしてきたか区別できない、しかしログイン済みであることはわかっているのでログアウトさせたい
        //         session()->flash('alert', '現在は管理者としてログインしています。ユーザー画面を利用するには一度ログアウトしてください！');
        //         session()->flash('alert-type', 'alert-error');

        //         return route('admin.index');
        //     }
        // }

        if ($request->form === LoginForm::ADMIN->value) {
            if (session('login_form') === LoginForm::ADMIN->value) {
                if (session('login_form') === LoginForm::ADMIN->value) {
                    return route('admin.index');
                }
            }
        }

        if (session('login_form') === LoginForm::GENERAL->value) {
            if ($request->form === LoginForm::ADMIN->value) {
                session([
                            'user_id' => $request->user()->id,
                            'login_form' => $request->form,
                            ]);
                return route('admin.index');

            }
        }

        return route('index');
    }
}
