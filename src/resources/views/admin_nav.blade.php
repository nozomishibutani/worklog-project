@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title></title>
@endsection

@section('link')
    <a href="">
        <img src="{{ asset('header_logo.png') }}" alt="ヘッダーロゴ画像">
    </a>
@endsection

@section('nav')
    @auth('admin')
        <nav class="header__nav">
            <ul class="header__list">
                <li class="header__item">
                    <a href="" class="header__link">勤怠一覧</a>
                </li>
                <li class="header__item">
                    <a href="" class="header__link">スタッフ一覧</a>
                </li>
                <li class="header__item">
                    <a href="" class="header__link">申請一覧</a>
                </li>
                <li class="header__item">
                    <form class="header__form" action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="from" value="{{ App\Enums\Role::ADMIN->value }}">
                        <button class="header__btn">ログアウト</button>
                    </form>
                </li>

            </ul>
        </nav>
    @endif
@endsection
