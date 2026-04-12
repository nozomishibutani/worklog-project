<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Enums\Role;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        if ($request->from === Role::ADMIN->value) {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }
}
