<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;
use App\Enums\Role;
use App\Enums\Guard;
use App\Enums\Type;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        /** @var \App\Models\User|null $user */
        $adminUser = Auth::guard(Guard::ADMIN->value)->user();

        // =====================
        // adminログイン
        // =====================
        if ($request->routeIs('admin.login')) {

            if ($adminUser && $adminUser->role !== Role::ADMIN) {
                Auth::guard(Guard::ADMIN->value)->logout();

                return redirect()->route('admin.login')
                    ->with('alert', 'ログイン情報が登録されていません')
                    ->with('alert-type', 'alert-error');
            }

            if ($adminUser && $adminUser->role === Role::ADMIN) {

                // userセッションが残ってたら消す
                Auth::guard(Guard::WEB->value)->logout();

                return redirect()->route('admin.index');
            }
        }

        // =====================
        // 一般ログイン
        // =====================
        if ($request->path('login')) {

            // adminセッションが残ってたら消す
            Auth::guard(Guard::ADMIN->value)->logout();

            return redirect()->route('user.index');
        }

        // =====================
        // 想定外
        // =====================
        return redirect()->route('login')
            ->with('alert', 'システムエラーが発生しました')
            ->with('alert-type', 'alert-error');
    }
}
