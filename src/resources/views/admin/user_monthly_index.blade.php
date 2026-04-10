@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title>スタッフ別勤怠一覧</title>
@endsection

@section('content')
    <div class="admin">
        <h1>{{ $name }}さんの勤怠</h1>
        <div>
            <nav class="">
                <ul class="">
                    <li class="">
                        <a class=""
                            href="{{ route('admin.monthly.index', ['id' => $userId, 'date' => $date['prev']]) }}">前月</a>
                    </li>
                    <li class="">
                        {{ $date['label'] }}
                    </li>
                    <li class="">
                        <a class=""
                            href="{{ route('admin.monthly.index', ['id' => $userId, 'date' => $date['next']]) }}">翌月</a>
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
                                href="{{ route('admin.show', ['id' => '', 'user_id' => $userId, 'date' => $workDate]) }}">詳細
                            </a>
                        @else
                            <a class="admin__detail-btn"
                                href="{{ route('admin.show', ['id' => $value['attendance_id']]) }}">詳細
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
        <div class="export">
            <a class="" href="{{ route('admin.export', ['user_id' => $userId, 'date' => $date['export']]) }}">
                <button class="export__btn btn" type="submit">CSV出力</button>
            </a>
        </div>
    </div><!-- admin-->
@endsection
