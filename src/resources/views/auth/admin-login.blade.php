<h1>管理者ログイン</h1>
<form method="POST" action="{{ route('admin.login') }}">
    @csrf
    <input type="email" name="email" placeholder="メール">
    <input type="password" name="password" placeholder="パスワード">
    <button>ログイン</button>
</form>