@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title></title>
@endsection

@section('content')
    <div class="admin">
        <h1>{{ $date->format('Y年m月d日') }}の勤怠</h1>
        <div>
            <nav class="">
                <ul class="">
                    <li class="">
                        <a class=""
                            href="{{ route('admin.daily.session', ['date' => $date->copy()->subDay()->format('Ymd')]) }}">前日</a>
                    </li>
                    </li>
                    <li class="">
                        {{ $date->format('Y/m/d') }}
                    </li>
                    <li class="">
                        <a class=""
                            href="{{ route('admin.daily.session', ['date' => $date->copy()->addDay()->format('Ymd')]) }}">翌日</a>
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
            @foreach ($workTimes as $userId => $time)
                <tr class="admin__row">
                    <td class="admin__data">{{ $time['name'] }}</td>
                    <td class="admin__data">{{ $time['clock_in'] }}</td>
                    <td class="admin__data">{{ $time['clock_out'] }}</td>
                    <td class="admin__data">{{ $breakTimes[$userId]['display_total'] }}</td>
                    <td class="admin__data">{{ $time['display_total'] }}</td>
                    <td class="admin__data">
                        {{-- <a class="admin__detail-btn"
                        href="{{ route('admin.monthly.session', ['to' => App\Enums\TYPE::PERSONALLY->value, 'id' => $userId, 'date' => $workDate]) }}">詳細
                        </a> --}}
                    </td>
                </tr>
            @endforeach
        </table>
    </div><!-- admin-->
@endsection
