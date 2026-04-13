<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/normalize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth/base.css') }}">
    <title>ログイン</title>
</head>

<body>
    <header class="header">
        <div class="header__container">
            <div class="header__logo">
                <a href="#">
                    <img src="{{ asset('header_logo.png') }}" alt="ヘッダーロゴ画像">
                </a>
            </div>
        </div>
    </header>
    <main>
        <div class="main__container">
            <div class="auth">
                <h1 class="auth-ttl">管理者ログイン</h1>
                @if ($errors->any())
                    <div class="alert alert-error">
                        <ul class="alert__list">
                            @foreach ($errors->all() as $error)
                                <li class="alert__item">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form class="form" action="/login" method="POST" novalidate>
                    @csrf
                    {{-- hidedn --}}
                    <input type="hidden" name="form" value="{{ App\Enums\LoginForm::ADMIN->value }}">
                    <ul class="auth__list">
                        <li class="auth__item">
                            <label class="auth__label" for="email">メールアドレス</label>
                            <input class="auth__form-input" type="email" name="email" id="email"
                                value="{{ old('email') }}" />
                        </li>

                        <li class="auth__item">
                            <label class="auth__label" for="password">パスワード</label>
                            <input class="auth__form-input" type="password" name="password" name="password"
                                id="password" />
                        </li>
                    </ul>
                    <button class="btn auth__btn">管理者ログインする</button>
                </form>
            </div><!-- auth -->
        </div><!-- main__container -->
    </main>

</body>

</html>
