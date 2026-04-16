@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/user_monthly_index.css') }}">
@endsection

@section('title')
    <title>スタッフ別勤怠一覧</title>
@endsection

@section('content')
    <div class="admin">
        <h1 class="admin-ttl">{{ $name }}さんの勤怠</h1>
        <nav class="admin__nav">
            <ul class="admin__list">
                <li class="admin__item">
                    <a class="link admin__link admin__link--month"
                        href="{{ route('admin.monthly.index', ['id' => $userId, 'date' => $date['prev']]) }}">
                        <span class="direction">←</span>
                        <span class="admin__item-day">前月</span>
                    </a>
                </li>
                <li class="admin__item">
                    <span class="admin__item-date">{{ $date['label'] }}</span>
                </li>
                <li class="admin__item">
                    <a class="link admin__link admin__link--month"
                        href="{{ route('admin.monthly.index', ['id' => $userId, 'date' => $date['next']]) }}">
                        <span class="admin__item-day">翌月</span>
                        <span class="direction">→</span>
                    </a>
                </li>
            </ul>
        </nav>
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
                            <a class="link admin__link admin__link--detail"
                                href="{{ route('admin.show', ['id' => '', 'user_id' => $userId, 'date' => $workDate]) }}">詳細
                            </a>
                        @else
                            <a class="link admin__link admin__link--detail"
                                href="{{ route('admin.show', ['id' => $value['attendance_id']]) }}">詳細
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
        <div class="export__link-box">
            <a class="" href="{{ route('admin.export', ['user_id' => $userId, 'date' => $date['export']]) }}">
                <button class="btn export__btn " type="submit">CSV出力</button>
            </a>
        </div>
    </div><!-- admin-->
@endsection
