<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/normalize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth/notice.css') }}">
    <title>メール認証</title>
</head>

<body>
    <header class="header">
        <div class="header__container">
            <div class="header__logo">
                <img src="{{ asset('header_logo.png') }}" alt="ヘッダーロゴ画像">
            </div>
        </div>
    </header>
    <main>
        <div class="main__container">
            <div class="verification">
                    <p class="verification__content">登録していただいたメールアドレスに認証メールを送付しました。<br>メール認証を完了してください。</p>
                    <div class="verification__link-box">
                        <a class="verification__link" href="{{ route('verification.confirm') }}">
                            認証はこちらから
                        </a>
                    </div>

                <div class="verification__btn-box">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button class="verification__btn">
                            認証メールを再送する
                        </button>
                    </form>
                </div>
            </div><!-- verification -->
        </div><!-- main__container -->
    </main>

    <!-- メール再送信完了したメッセージ-->
    @if (session('status') === 'verification-link-sent')
        <script>
            alert("認証メールを再送信しました");
        </script>
    @endif

</body>
</html>
