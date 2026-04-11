@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title>月次勤怠一覧</title>
@endsection

@section('content')
    <div class="admin">
        <h1>勤怠一覧</h1>
        <div>
            <nav class="">
                <ul class="">
                    <li class="">
                        <a class=""
                            href="{{ route('monthly.index', ['id' => $userId, 'date' => $date['prev']]) }}">前月</a>
                    </li>
                    <li class="">
                        {{ $date['label'] }}
                    </li>
                    <li class="">
                        <a class=""
                            href="{{ route('monthly.index', ['id' => $userId, 'date' => $date['next']]) }}">翌月</a>
                    </li>
                </ul>
            </nav>
        </div>

        <table class="admin__table">
            <tr class="admin__row">
                <th class="admin__label">日付</th>
                <th class="admin__label">出勤</th>
                <th class="admin__label">退勤</th>
                <th class="admin__label">休憩</th>
                <th class="admin__label">合計</th>
                <th class="admin__label">詳細</th>
            </tr>
            @foreach ($workTimes as $workDate => $value)
                <tr class="admin__row">
                    <td class="admin__data">{{ $value['display_date'] }}</td>
                    <td class="admin__data">{{ $value['clock_in'] }}</td>
                    <td class="admin__data">{{ $value['clock_out'] }}</td>
                    <td class="admin__data">{{ $breakTimes[$workDate]['display_total'] ?? null }}</td>
                    <td class="admin__data">{{ $value['display_total'] }}</td>
                    <td class="admin__data">
                        @if (is_null($value['attendance_id']))
                            <a class="admin__detail-btn"
                                href="{{ route($route, ['id' => '', 'date' => $workDate]) }}">詳細
                            </a>
                        @else
                            <a class="admin__detail-btn"
                                href="{{ route($route, ['id' => $value['attendance_id']]) }}">詳細
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div><!-- admin-->
@endsection
