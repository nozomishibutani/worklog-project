<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LoginTest extends TestCase
{
    /**
     * ログイン認証機能（一般ユーザー）
     */
    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    #[Test]
    public function emailIsRequired() {
        // 1. ユーザーを登録する
        $user = User::factory()->create();

        // 2. メールアドレス以外のユーザー情報を入力する
        // 3. ログインの処理を行う
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
    public function passwordIsRequired() {
        // 1. ユーザーを登録する
        $user = User::factory()->create();

        // 2. パスワード以外のユーザー情報を入力する
        // 3. ログインの処理を行う"
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
    public function loginFailsForNonExistentUser() {
        // 1. ユーザーを登録する
        $user = User::factory()->create();

        // 2. 誤ったメールアドレスのユーザー情報を入力する
        // 3. ログインの処理を行う"
        $response = $this->post('/login', [
            'email' => 'notexist@example.com',
            'password' => 'testpass',
        ]);

        // 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        // 認証されていないことを確認
        $this->assertGuest();
    }
}
