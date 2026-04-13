<?php

namespace App\Http\Responses;

use App\Enums\LoginForm;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        session()->forget(['user_id','login_form']);

        // =====================
        // adminログイン
        // =====================
        if ($request->form === LoginForm::ADMIN->value) {
            if (!$request->user() || !$request->user()->isAdmin()) {
                Auth::logout();
                return redirect()->route('admin.login')
                    ->with('alert', 'ログイン情報が登録されていません')
                    ->with('alert-type', 'alert-error');
            }

            if ($request->user() && $request->user()->isAdmin()) {
                session([
                    'user_id' => $request->user()->id,
                    'login_form' => $request->form,
                    ]);
                return redirect()->route('admin.index');
            }
        }

        // =====================
        // 一般ログイン
        // =====================
        if ($request->form === LoginForm::GENERAL->value) {
            session([
                'user_id' => $request->user()->id,
                'login_form' => $request->form,
                ]);
            return redirect()->route('index');
        }
    }
}
