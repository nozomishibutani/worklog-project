<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestHelper
{
    use RefreshDatabase;

    /**
     * @test
     * ユーザー作成
     */
    protected function createVerifiedUser() {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $user->markEmailAsVerified();
        return $user;
    }
}
