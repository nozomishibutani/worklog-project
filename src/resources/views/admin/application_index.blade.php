@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title>申請一覧</title>
@endsection

@section('content')
    <div class="admin">
        <h1>申請一覧</h1>
        <div>
            <nav class="">
                <ul class="">
                    <li class="">
                    <li class="">
                        <a class="" href="{{ route('admin.pending') }}">承認待ち</a>
                    </li>
                    </li>
                    <li class="">
                        <a class="" href="{{ route('admin.approved') }}">承認済み</a>
                    </li>
                    </li>
                </ul>
            </nav>
        </div>

        <table class="admin__table">
            <tr class="admin__row">
                <th class="admin__label">状態</th>
                <th class="admin__label">名前</th>
                <th class="admin__label">対象日時</th>
                <th class="admin__label">申請理由</th>
                <th class="admin__label">申請日時</th>
                <th class="admin__label">詳細</th>
            </tr>
            @foreach ($workTimes as $userId => $time)
                <tr class="admin__row">
                    <td class="admin__data">{{ $time['name'] }}</td>
                    <td class="admin__data">{{ $time['name'] }}</td>
                    <td class="admin__data">{{ $time['work_date'] }}</td>
                    <td class="admin__data">{{ $time['note'] }}</td>
                    <td class="admin__data">{{ $time[''] }}</td>
                    <td class="admin__data">
                        <a class="admin__detail-btn"
                            href="{{ route('admin.session', ['id' => $userId, 'date' => $date['detail']]) }}">詳細
                        </a>
                    </td>
                </tr>
            @endforeach
        </table>
    </div><!-- admin-->
@endsection
