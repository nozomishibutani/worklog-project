@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title></title>
@endsection

@section('content')
    <div class="admin">
        <h1>{{ $date['title'] }}の勤怠</h1>
        <div>
            <nav class="">
                <ul class="">
                    <li class="">
                        <a class="" href="{{ route('admin.index', ['date' => $date['prev']]) }}">前日</a>
                    </li>
                    </li>
                    <li class="">
                        {{ $date['label'] }}
                    </li>
                    <li class="">
                        <a class="" href="{{ route('admin.index', ['date' => $date['next']]) }}">翌日</a>
                    </li>
                    </li>
                </ul>
            </nav>
        </div>

        <table class="admin__table">
            <tr class="admin__row">
                <th class="admin__label">お名前</th>
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
                        {{-- <a class="admin__detail-btn"
                        href="{{ route('admin.session', ['id' => $userId, 'date' => $date['detail']]) }}">詳細 --}}
                        @if (is_null($val['attendance_id']))
                            <a class="admin__detail-btn"
                                href="{{ route('admin.show', ['id' =>'', 'user_id' => $userId, 'date' => $date['detail']] ) }}">詳細
                            </a>
                        @else
                            <a class="admin__detail-btn"
                                href="{{ route('admin.show', ['id' => $val['attendance_id']]) }}">詳細
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div><!-- admin-->
@endsection
