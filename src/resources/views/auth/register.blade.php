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
                <h1 class="auth-ttl">会員登録</h1>
                @if ($errors->any())
                    <div class="alert alert-error">
                        <ul class="alert__list">
                            @foreach ($errors->all() as $error)
                                <li class="alert__item">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form class="form" action="/register" method="post" novalidate>
                    @csrf
                    <ul class="auth__list">
                        <li class="auth__item">
                            <label class="auth__label" for="name">名前</label>
                            <input class="auth__form-input" type="text" name="name" value="{{ old('name') }}" />
                        </li>

                        <li class="auth__item">
                            <label class="auth__label" for="email">メールアドレス</label>
                            <input class="auth__form-input" type="email" name="email" id="email"
                                value="{{ old('email') }}" />
                        </li>

                        <li class="auth__item">
                            <label class="auth__label" for="password">パスワード</label>
                            <input class="auth__form-input" type="password" name="password" id="password" />
                        </li>

                        <li class="auth__item">
                            <label class="auth__label" for="password_confirmation">パスワード確認</label>
                            <input class="auth__form-input" type="password" name="password_confirmation"
                                id="password_confirmation" />
                        </li>
                    </ul>
                    <button class="btn auth__btn">登録する</button>
                </form>

                <div class="auth__link-box">
                    <a class="auth__link" href="/login">ログインはこちら</a>
                </div>
            </div><!-- auth -->
        </div><!-- main__container -->
    </main>

</body>

</html>
