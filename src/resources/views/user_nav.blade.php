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
    @auth('web')
        <nav class="header__nav">
            <ul class="header__list">
                <li class="header__item">
                    <a href="" class="header__link">勤怠</a>
                </li>
                <li class="header__item">
                    <a href="" class="header__link">勤怠一覧</a>
                </li>
                <li class="header__item">
                    <a href="" class="header__link">申請</a>
                </li>
                <li class="header__item">
                    <li class="header__item">
                    <form class="header__form" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <input type="hidden" name="from" value="{{ App\Enums\Role::USER->value }}">
                        <button class="header__btn">ログアウト</button>
                    </form>
                </li>
                </li>

            </ul>
        </nav>
    @endif
@endsection

@section('content')
一般画面
@endsection