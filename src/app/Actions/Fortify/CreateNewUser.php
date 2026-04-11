<?php

namespace App\Actions\Fortify;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        // 会員登録時のバリデーションはRegisterRequestを使用
        app(RegisterRequest::class)->validateResolved();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'role' => Role::USER->value,
            'password' => Hash::make($input['password']),
        ]);
    }
}
