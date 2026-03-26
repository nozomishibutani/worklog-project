@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title>勤怠詳細画面</title>
@endsection

@section('content')
    <div class="admin">
        <h1>勤怠詳細</h1>
        <form class="" action="post">
            <table class="admin__table">
                @csrf
                @foreach ($workTimes as $key => $value)
                    <tr class="admin__row">
                        <th class="admin__label">名前</th>
                        <td class="admin__data">{{ $value['name'] }}</td>
                    </tr>
                    <tr class="admin__row">
                        <th class="admin__label">日付</th>
                        <td class="admin__data">{{ $year }}</td>
                        <td class="admin__data">{{ $monthDay }}</td>
                    </tr>
                    <tr class="admin__row">
                        <th class="admin__label">出勤・退勤</th>
                        <td class="admin__data">{{ $value['clock_in'] }}</td>
                        <td class="admin__data">{{ $value['clock_out'] }}</td>
                    </tr>
                    {{-- 休憩繰り返し分回す --}}
                    @foreach ($breakTimes as $key => $breakTime)
                        <tr class="admin__row">
                            <th class="admin__label">休憩</th>
                            <td class="admin__data">{{ $breakTime['hours'] }}</td>
                            <td class="admin__data">{{ $breakTime['minutes'] }}
                        </tr>
                    @endforeach
                    <tr class="admin__row">
                        <th class="admin__label">備考</th>
                        <td class="admin__data">
                            <textarea name="">{{ $value['note'] }}</textarea>
                        </td>
                    </tr>
                @endforeach
            </table>
            <div class="">
                <button class="">修正</button>
            </div>
        </form>
    </div><!-- admin-->
@endsection
