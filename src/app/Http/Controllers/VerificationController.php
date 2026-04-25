<?php

namespace App\Http\Controllers;

use App\Enums\LoginForm;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    /**
     * このコントローラーは VerifyEmail 通知を利用したメール認証用です。
     * VerifyEmail 通知ではデフォルトで以下3つのルートを使用します：
     * 1. verification.notice      -> 確認メール送信後の画面
     * 2. verification.verify      -> メール内のリンククリックで確認完了
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('register')
                            ->with('alert', 'システムエラーが発生しました。')
                            ->with('alert-type', 'alert--error');

        }
        if (!$user->hasVerifiedEmail()) {
            // email_verified_at に日時セット
            $user->markEmailAsVerified();
            session([
                'user_id' => $user->id,
                'login_form' => LoginForm::GENERAL->value,
            ]);
        }
        return redirect()->route('index');
    }

    /**
     * テスト用メール認証URL作成
     */
    public function confirm()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('register')
                            ->with('alert', 'システムエラーが発生しました。')
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
