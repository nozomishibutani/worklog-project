<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminLoginTest extends TestCase
{
    /**
     * ログイン認証機能（管理者）
     */
    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    #[Test]
    public function emailIsRequired()
    {
        // 1. ユーザーを登録する
        $user = User::factory()->create(['role' => Role::ADMIN]);

        // 2. メールアドレス以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        // ログイン処理は管理者も/loginで行っている
        $response = $this->get(route('admin.login'));
        $response->assertStatus(200);
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        // 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    #[Test]
    public function passwordIsRequired()
    {
        // 1. ユーザーを登録する
        $user = User::factory()->create(['role' => Role::ADMIN]);

        // 2. パスワード以外のユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->get(route('admin.login'));
        $response->assertStatus(200);
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        // 「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    #[Test]
    public function loginFailsForNonExistentUser()
    {
        // 1. ユーザーを登録する
        $user = User::factory()->create(['role' => Role::ADMIN]);

        // 2. 誤ったメールアドレスのユーザー情報を入力する
        // 3. ログインの処理を行う
        $response = $this->get(route('admin.login'));
        $response->assertStatus(200);
        $response = $this->post('/login', [
            'email' => 'notexistadmin@example.com',
            'password' => 'password',
        ]);

        // 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        // 認証されていないことを確認
        $this->assertGuest();
    }
}
