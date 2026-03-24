<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Enums\Guard;
use Illuminate\Support\Facades\Auth;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        $from = $request->input('from');

        if ($from === Guard::ADMIN->value) {
            Auth::guard(Guard::ADMIN->value)->logout();
            return redirect()->route('admin.login');
        }
        return redirect()->route('login');
    }
}
