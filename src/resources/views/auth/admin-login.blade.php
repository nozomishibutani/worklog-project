@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth/base.css') }}">
@endsection

@section('title')
    <title>ログイン</title>
@endsection

@section('link')
    <a href="">
        <img src="{{ asset('header_logo.png') }}" alt="ヘッダーロゴ画像">
    </a>
@endsection

@section('content')
    <h1>管理者ログイン</h1>
    {{-- バリデーションエラー --}}
    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}">
        @csrf
        <input type="email" name="email" placeholder="メール">
        <input type="password" name="password" placeholder="パスワード">
        <button>ログイン</button>
    </form>
@endsection
