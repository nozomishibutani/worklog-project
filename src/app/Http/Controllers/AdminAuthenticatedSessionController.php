<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse;
use App\Enums\Guard;

class AdminAuthenticatedSessionController extends Controller
{
    public function create()
    {
        if (Auth::guard(Guard::ADMIN->value)->check()) {
            return redirect()->route('admin.index');
        }

        return view('auth.admin-login');
    }

    public function destroy (Request $request, LogoutResponse $response) {

        // ──────────────────────────────────────────────────────────────
        // Fortify 標準の logout は auth:web ミドルウェアを通すため、
        // 管理画面（admin）では正常にログアウトできない。
        // そこで、admin 用に専用処理を自作して明示的にログアウトさせる。
        // ──────────────────────────────────────────────────────────────

        Auth::guard(Guard::ADMIN->value)->logout();

        // セッション破棄
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // LogoutResponse に遷移
        return $response->toResponse($request);
    }
}
