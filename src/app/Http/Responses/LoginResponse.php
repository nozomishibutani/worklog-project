<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;
use App\Enums\Role;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        session()->forget(['user_id','role']);

        // =====================
        // adminログイン
        // =====================
        if ($request->from === Role::ADMIN->value) {
            if (!$request->user() || !$request->user()->isAdmin()) {
                Auth::logout();
                return redirect()->route('admin.login')
                    ->with('alert', 'ログイン情報が登録されていません')
                    ->with('alert-type', 'alert-error');
            }

            if ($request->user() && $request->user()->isAdmin()) {
                session([
                    'user_id' => $request->user()->id,
                    'role' => $request->from,
                    ]);
                return redirect()->route('admin.index');
            }
        }

        // =====================
        // 一般ログイン
        // =====================
        if ($request->from === Role::USER->value) {
            session([
                'user_id' => $request->user()->id,
                'role' => $request->from,
                ]);
            return redirect()->route('index');
        }
    }
}
