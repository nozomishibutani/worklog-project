<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class RegisterTest extends TestCase
{
    /**
     * 認証機能（一般ユーザー）
     */
    use RefreshDatabase;

    /**
     * 名前が未入力の場合、バリデーションメッセージが表示される
     */
    #[Test]
    public function nameIsRequired() {
        // 1. 名前以外のユーザー情報を入力する
        // 2. 会員登録の処理を行う
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'sample@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        //「お名前を入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    #[Test]
    public function emailIsRequired() {
        // 1. メールアドレス以外のユーザー情報を入力する
        // 2. 会員登録の処理を行う
        $response = $this->post('/register', [
            'name' => 'sampleuser',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // 「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    #[Test]
    public function passwordMustBeAtLeast8Characters() {
        // 1. パスワードを8文字未満にし、ユーザー情報を入力する
        // 2. 会員登録の処理を行う
        $response = $this->post('/register', [
            'name' => 'sampleuser',
            'email' => 'sample@example.com',
            'password' => str_repeat('a', 7),
            'password_confirmation' => str_repeat('a', 7),
        ]);

        // 「パスワードは8文字以上で入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    #[Test]
    public function passwordConfirmationMustMatch() {
        // 1. 確認用のパスワードとパスワードを一致させず、ユーザー情報を入力する
        // 2. 会員登録の処理を行う
        $response = $this->post('/register', [
            'name' => 'sampleuser',
            'email' => 'sample@example.com',
            'password' => str_repeat('a', 8),
            'password_confirmation' => str_repeat('A', 8),
        ]);

        // 「パスワードと一致しません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    #[Test]
    public function passwordIsRequired() {
        // 1. パスワード以外のユーザー情報を入力する
        // 2. 会員登録の処理を行う"
        $response = $this->post('/register', [
            'name' => 'sampleuser',
            'email' => 'sample@example.com',
            'password' => '',
            'password_confirmation' => 'password',
        ]);

        //「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    #[Test]
    public function canRegisterAndRedirectToProfile() {
        // NotificationをFakeにする
        Notification::fake();

        // 1. ユーザー情報を入力する
        // 2. 会員登録の処理を行う
        $this->post('/register', [
        'name' => 'sampleuser',
        'email' => 'sample123@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ]);

        // データベースに登録したユーザー情報が保存される
        $this->assertDatabaseHas('users', [
            'name' => 'sampleuser',
            'email' => 'sample123@example.com',
        ]);
    }
}