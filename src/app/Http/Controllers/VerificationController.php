<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class VerificationController extends Controller
{
    /**
     * このコントローラーは VerifyEmail 通知を利用したメール認証用です。
     * VerifyEmail 通知ではデフォルトで以下3つのルートを使用します：
     * 1. verification.notice      -> 確認メール送信後の画面
     * 2. verification.verify      -> メール内のリンククリックで確認完了
     * 3. verification.send        -> メール再送信
     *
     * ビューは自由にカスタマイズ可能ですが、ルート名は変更不可です。
     */

    public function notice()
    {
        return view('auth.notice');
    }

    /**
     * テスト用メール認証処理
     */
    public function verify()
    {
        $user = User::find(session('unverified_user_id'));
        if (!$user) {
            return redirect()->route('login');
        }
        if (!$user->hasVerifiedEmail()) {
            // email_verified_at に日時セット
            $user->markEmailAsVerified();
            // ログイン
            Auth::login($user);
        }
        session()->forget('unverified_user_id');

        return redirect()->route('index');
    }

    public function resend()
    {
        $user = User::find(session('unverified_user_id'));
        $user->sendEmailVerificationNotification();

        return back()->with('message', '認証メールを再送信しました');
    }

    /**
     * テスト用メール認証URL作成
     */
    public function confirm()
    {
        $user = User::find(session('unverified_user_id'));
        if (!$user) {
            // 会員登録とメール認証のブラウザが異なるとセッションがないのでエラー
            return redirect()->route('login')
                            ->with('alert', 'システムエラーが発生しました。再度ログインしてメール認証を行ってください。')
                            ->with('alert-type', 'alert--error');
        }
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        return view('auth.confirm', compact('url'));
    }
}
