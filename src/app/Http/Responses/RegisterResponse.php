<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Illuminate\Support\Facades\Auth;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        // 新規登録後メール認証へ遷移
        if ($request->user() && !$request->user()->hasVerifiedEmail()) {
            session(['unverified_user_id' => $request->user()->id]);
            Auth::logout();
            return redirect()->route('verification.notice');
        }

        return redirect()->route('index');
    }
}
