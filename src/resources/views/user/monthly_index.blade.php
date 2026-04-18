@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/user/monthly_index.css') }}">
@endsection

@section('title')
    <title>{{ $date['label'] }}勤怠一覧</title>
@endsection

@section('content')
    <div class="general">
        <h1 class="general-ttl">勤怠一覧</h1>
        <nav class="general__nav">
            <ul class="general__list">
                <li class="general__item">
                    <a class="link general__link general__link--month"
                        href="{{ route('monthly.index', ['id' => $userId, 'date' => $date['prev']]) }}">
                        <span class="direction">←</span>
                        <span class="general__item-day">前月</span>
                    </a>
                </li>
                <li class="general__item">
                    <span class="general__item-date">{{ $date['label'] }}</span>
                </li>
                <li class="general__item">
                    <a class="link general__link general__link--month"
                        href="{{ route('monthly.index', ['id' => $userId, 'date' => $date['next']]) }}">
                        <span class="general__item-day">翌月</span>
                        <span class="direction">→</span>
                    </a>
                </li>
            </ul>
        </nav>
        <table class="general__table">
            <tr class="general__row">
                <th class="general__label">日付</th>
                <th class="general__label">出勤</th>
                <th class="general__label">退勤</th>
                <th class="general__label">休憩</th>
                <th class="general__label">合計</th>
                <th class="general__label">詳細</th>
            </tr>
            @foreach ($workTimes as $workDate => $value)
                <tr class="general__row">
                    <td class="general__data">{{ $value['display_date'] }}</td>
                    <td class="general__data">{{ $value['clock_in'] }}</td>
                    <td class="general__data">{{ $value['clock_out'] }}</td>
                    <td class="general__data">{{ $breakTimes[$workDate]['display_total'] ?? null }}</td>
                    <td class="general__data">{{ $value['display_total'] }}</td>
                    <td class="general__data">
                        @if (is_null($value['attendance_id']))
                            <a class="link general__link general__link--detail"
                                href="{{ route($route, ['id' => '', 'date' => $workDate]) }}">詳細
                            </a>
                        @else
                            <a class="link general__link general__link--detail"
                                href="{{ route($route, ['id' => $value['attendance_id']]) }}">詳細
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div><!-- general-->
@endsection
