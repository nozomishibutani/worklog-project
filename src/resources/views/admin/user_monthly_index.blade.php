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
                            href="{{ route('admin.session', ['date' => $date['prev'], 'to' => App\Enums\TYPE::MONTHLY->value, 'id' => $userId]) }}">前月</a>
                    </li>
                    <li class="">
                        {{ $date['label'] }}
                    </li>
                    <li class="">
                        <a class=""
                            href="{{ route('admin.session', ['date' => $date['next'], 'to' => App\Enums\TYPE::MONTHLY->value, 'id' => $userId, ]) }}">翌月</a>
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
                        <a class="admin__detail-btn"
                            href="{{ route('admin.session', ['date' => $workDate, 'to' => App\Enums\TYPE::PERSONALLY->value, 'id' => $userId]) }}">詳細</a>
                    </td>
                </tr>
            @endforeach
        </table>
    </div><!-- admin-->
@endsection
