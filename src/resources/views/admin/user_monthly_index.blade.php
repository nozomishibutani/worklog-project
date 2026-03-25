@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title>スタッフ別勤怠一覧</title>
@endsection

@section('content')
    <div class="admin">
        @php
            $time = collect($workTimes)->first();
        @endphp

        @if ($time)
            <h1>{{ $time['name'] }}さんの勤怠</h1>
        @endif
        <div>
            <nav class="">
                <ul class="">
                    <li class="">
                        <form action="{{-- route('admin.change_month') --}}" method="post">{{-- クエリパラメータつけてもいいか確認 --}}
                            @csrf
                            <input type="hidden" name="change_date" value="{{ $date->copy()->subMonth() }}">
                            <button class="btn">前月</button>
                        </form>
                    </li>
                    <li class="">
                        {{ $date->format('Y/m/d') }}
                    </li>
                    <li class="">
                        <form action="{{-- route('admin.change_month') --}}" method="post">
                            @csrf
                            <input type="hidden" name="change_date" value="{{ $date->copy()->addMonth() }}">
                            <button class="btn">翌月</button>
                        </form>
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
            @foreach ($workTimes as $userId => $time)
                <tr class="admin__row">
                    <th class="admin__label">{{ $time['work_date'] }}</th>
                    <td class="admin__data">{{ $time['clock_in'] }}</td>
                    <td class="admin__data">{{ $time['clock_out'] }}</td>
                    <td class="admin__data">{{ $breakTimes[$userId]['display'] }}</td>
                    <td class="admin__data">{{ $time['display'] }}</td>
                    <td class="admin__data">
                        <a class="admin__detail-btn"
                            href="{{ route('admin.attendance.daily.show', ['id' => $userId]) }}">詳細</a>
                    </td>
                </tr>
            @endforeach
    </div><!-- admin-->
@endsection
