@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title></title>
@endsection

@section('link')
    <a href="{{ route('') }}">
        <img src="{{ asset('/images/header_logo.png') }}" alt="ヘッダーロゴ画像">
    </a>
@endsection

@section('nav')
    @if (Auth::check())
        <nav class="header__nav">
            <ul class="header__list">
                <li class="header__item">
                    <a href="{{ route() }}" class="header__link">勤怠一覧</a>
                </li>
                <li class="header__item">
                    <a href="{{ route() }}" class="header__link">スタッフ一覧</a>
                </li>
                <li class="header__item">
                    <a href="{{ route() }}" class="header__link">申請一覧</a>
                </li>
                <li class="header__item">
                    <form class="header__form--logout" action="/logout" method="post">
                        @csrf
                        <button class="header__btn">ログアウト</button>
                    </form>
                </li>

            </ul>
        </nav>
    @endif
@endsection
