<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/normalize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('title')
</head>

<body>
    <header class="header">
        <div class="header__container">
            <div class="header__logo">
                <a href="#">
                    <img src="{{ asset('header_logo.png') }}" alt="ヘッダーロゴ画像">
                </a>
            </div>
            <!-- 管理画面 -->
            @if (Auth::guard('admin')->check() && !Auth::guard('web')->check())
                <nav class="header__nav">
                    <ul class="header__list">
                        <li class="header__item">
                            <a href="{{ route('admin.index') }}" class="link header__link">勤怠一覧</a>
                        </li>
                        <li class="header__item">
                            <a href="{{ route('admin.user.index') }}" class="link header__link">スタッフ一覧</a>
                        </li>
                        <li class="header__item">
                            <a href="{{ route('application.index') }}" class="link header__link">申請一覧</a>
                        </li>
                        <li class="header__item">
                            <form class="header__form" action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="from" value="{{ App\Enums\Role::ADMIN->value }}">
                                <button class="btn header__btn">ログアウト</button>
                            </form>
                        </li>
                    </ul>
                </nav>
            @endif

            <!-- ユーザー画面 -->
            @if (!Auth::guard('admin')->check() && Auth::guard('web')->check())
                <nav class="header__nav">
                    <ul class="header__list">
                        <li class="header__item">
                            <a href="" class="link header__link">勤怠</a>
                        </li>
                        <li class="header__item">
                            <a href="" class="link header__link">勤怠一覧</a>
                        </li>
                        <li class="header__item">
                            <a href="{{ route('application.index') }}" class="link header__link">申請</a>
                        </li>
                        <li class="header__item">
                        <li class="header__item">
                            <form class="header__form" action="{{ route('logout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="from" value="{{ App\Enums\Role::USER->value }}">
                                <button class="btn header__btn">ログアウト</button>
                            </form>
                        </li>
                        </li>
                    </ul>
                </nav>
            @endif
        </div>
    </header>

    <main>
        <div class="main__container">
            @yield('content')
        </div>
    </main>
    @yield('js')
</body>

</html>
