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
                @yield('link')
            </div>
            @yield('nav')
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
