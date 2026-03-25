@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title>スタッフ一覧</title>
@endsection

@section('content')
    <div class="admin">
        <h1>スタッフ一覧</h1>

        <table class="admin__table">
            <tr class="admin__row">
                <th class="admin__label">名前</th>
                <th class="admin__label">メールアドレス</th>
                <th class="admin__label">月次勤怠</th>
            </tr>
            @foreach ($users as $user)
                <tr class="admin__row">
                    <td class="admin__data">{{ $user->name }}</td>
                    <td class="admin__data">{{ $user->email }}</td>
                    <td class="admin__data">
                        <a class="admin__detail-btn" href="{{ route('admin.monthly.index',  ['id' => $user->id]) }}">詳細</a>
                    </td>
                </tr>
            @endforeach
    </div><!-- admin-->
@endsection
