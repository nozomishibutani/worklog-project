<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class EmailVerificationTest extends TestCase
{
    /**
     * メール認証機能
     */
    use RefreshDatabase;

    /**
     * 会員登録後、認証メールが送信される
     */
    #[Test]
    public function sendVerificationEmailAfterRegistration() {
        // NotificationをFakeにする
        Notification::fake();

        // 1. 会員登録をする
        // 2. 認証メールを送信する
        $this->post('/register', [
            'name' => 'sampleuser',
            'email' => 'sample@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // 登録したメールアドレス宛に認証メールが送信されている
        $user = User::where([
            'name' => 'sampleuser',
            'email' => 'sample@example.com',
        ])->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    #[Test]
    public function redirectToVerificationSite() {
        // NotificationをFakeにする
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'sampleuser',
            'email' => 'sample@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // 1. メール認証導線画面を表示する
        $response->assertRedirect(route('verification.notice'));

        // 2. 「認証はこちらから」ボタンを押下
        $response = $this->get(route('verification.confirm'));

        // 3. メール認証サイトを表示する
        $response->assertStatus(200)
            ->assertViewIs('auth.confirm')
            ->assertSee('Please click the button below to verify your email address.');
    }

    /**
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    #[Test]
    public function redirectToAttendanceLogAfterVerification() {
        // NotificationをFakeにする
        Notification::fake();

        $response = $this->post('/register', [
        'name' => 'sampleuser',
        'email' => 'sample@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        ]);

        $user = User::where([
            'name' => 'sampleuser',
            'email' => 'sample@example.com',
        ])->first();

        // 認証リンクを作成
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // 1. メール認証を完了する
        $response = $this->actingAs($user)->get($verifyUrl);
        $this->assertNotNull($user->fresh()->email_verified_at);

        // 2. 勤怠登録画面を表示する
        // 勤怠登録画面に遷移する
        $response->assertRedirect(route('index'));
    }
}