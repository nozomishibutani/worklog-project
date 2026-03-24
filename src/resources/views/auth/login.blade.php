<h1>ユーザーログイン</h1>
<form method="POST" action="{{ route('login') }}">
    @csrf
    <input type="email" name="email" placeholder="メール">
    <input type="password" name="password" placeholder="パスワード">
    <button>ログイン</button>
</form>