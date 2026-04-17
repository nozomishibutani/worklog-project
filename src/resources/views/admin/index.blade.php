@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
@endsection

@section('title')
    <title>{{ $date['title'] }}の勤怠</title>
@endsection

@section('content')
    <div class="admin">
        @if (session('alert'))
            <div class="alert {{ session('alert-type', 'alert-success') }}">
                <p>{{ session('alert') }}</p>
            </div>
        @endif
        <h1 class="admin-ttl">{{ $date['title'] }}の勤怠</h1>
        <nav class="admin__nav">
            <ul class="admin__list">
                <li class="admin__item">
                    <a class="link admin__link admin__link--day" href="{{ route('admin.index', ['date' => $date['prev']]) }}">
                        <span class="direction">←</span>
                        <span class="admin__item-day">前日</span>
                    </a>
                </li>
                <li class="admin__item">
                    <span class="admin__item-date">{{ $date['label'] }}</span>
                </li>
                <li class="admin__item">
                    <a class="link admin__link admin__link--day"
                        href="{{ route('admin.index', ['date' => $date['next']]) }}">
                        <span class="admin__item-day">翌日</span>
                        <span class="direction">→</span>
                    </a>
                </li>
            </ul>
        </nav>
        <table class="admin__table">
            <tr class="admin__row">
                <th class="admin__label">名前</th>
                <th class="admin__label">出勤</th>
                <th class="admin__label">退勤</th>
                <th class="admin__label">休憩</th>
                <th class="admin__label">合計</th>
                <th class="admin__label">詳細</th>
            </tr>
            @foreach ($workTimes as $userId => $val)
                <tr class="admin__row">
                    <td class="admin__data">{{ $val['name'] }}</td>
                    <td class="admin__data">{{ $val['clock_in'] }}</td>
                    <td class="admin__data">{{ $val['clock_out'] }}</td>
                    <td class="admin__data">{{ $breakTimes[$userId]['display_total'] ?? null }}</td>
                    <td class="admin__data">{{ $val['display_total'] }}</td>
                    <td class="admin__data">
                        <a class="link admin__link admin__link--detail"
                            href="{{ route('admin.show', ['id' => $val['attendance_id']]) }}">詳細</a>
                    </td>
                </tr>
            @endforeach
        </table>
    </div><!-- admin-->
@endsection
