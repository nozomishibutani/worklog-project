<?php

namespace App\Http\Responses;

use App\Enums\LoginForm;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        if ($request->form === LoginForm::ADMIN->value) {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }
}
