@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    @if ($mode === 'approve')
        <title>修正申請承認画面</title>
    @elseif ($mode === 'show')
        <title>勤怠詳細画面</title>
    @endif
@endsection

@section('content')
    <div class="admin">
        <h1>勤怠詳細</h1>
        @if (session('alert'))
            <div class="alert {{ session('alert-type', 'alert-success') }}">
                <p>{{ session('alert') }}</p>
            </div>
        @endif
        @if ($mode === 'approve')
            <form class="" action="{{-- route('admin.approval') --}}" method="post" novalidate>
            @elseif ($mode === 'show')
                <form class="" action="{{ route('admin.update') }}" method="post" novalidate>
        @endif
        @csrf
        {{-- hidden --}}
        <input type="hidden" name="user_id" value="{{ $workTimes['userId'] }}">
        <input type="hidden" name="attendance_id" value="{{ $workTimes['attendanceId'] }}">
        <table class="admin__table">
            <tr class="admin__row">
                <th class="admin__label">名前</th>
                <td class="admin__data">{{ $workTimes['name'] }}</td>
            </tr>
            <tr class="admin__row">
                <th class="admin__label">日付</th>
                <td class="admin__data">
                    <input type="text" name="year" value="{{ $workDate['year'] }}" readonly>年
                </td>
                <td class="admin__data">
                    <input type="text" name="month" value="{{ $workDate['month'] }}" readonly>月
                    <input type="text" name="day" value="{{ $workDate['day'] }}" readonly>日
                </td>
            </tr>
            <tr class="admin__row">
                <th class="admin__label">出勤・退勤</th>
                <td class="admin__data">
                    <input type="time" name="work_in" value="{{ old('work_in', $workTimes['clock_in']) }}">
                </td>
                <td class="admin__data">～</td>
                <td class="admin__data">
                    <input type="time" name="work_out" value="{{ old('work_out', $workTimes['clock_out']) }}">
                </td>
                @if ($errors->has('work_in') || $errors->has('work_out'))
                    <td class="msg">
                        {{ $errors->first('work_in') }}
                        {{ $errors->first('work_out') }}
                    </td>
                @endif
            </tr>
            {{-- 休憩なし --}}
            @if (count($breakTimes) == 0)
                <tr class="admin__row">
                    <th class="admin__label">休憩</th>
                    <td class="admin__data">
                        <input type="time" name="break_in[0]" value="{{ old('break_in.0') }}">
                    </td>
                    <td class="admin__data">～</td>
                    <td class="admin__data">
                        <input type="time" name="break_out[0]" value="{{ old('break_out.0') }}">
                    </td>
                    @if ($errors->has('break_in.0') || $errors->has('break_out.0'))
                        <td class="msg">
                            {{ $errors->first('break_in.0') }}
                            {{ $errors->first('break_out.0') }}
                        </td>
                    @endif
                </tr>
            @elseif (count($breakTimes) > 0)
                {{-- 休憩があり --}}
                @for ($i = 0; $i < count($breakTimes); $i++)
                    <tr class="admin__row">
                        <th class="admin__label">休憩{{ $i > 1 ? $i : '' }}</th>
                        <td class="admin__data">
                            <input type="time" name="break_in[{{ $breakTimes[$i]['id'] }}]"
                                value="{{ old('break_in.' . $breakTimes[$i]['id'], $breakTimes[$i]['clock_in']) }}">
                        </td>
                        <td class="admin__data">～</td>
                        <td class="admin__data">
                            <input type="time" name="break_out[{{ $breakTimes[$i]['id'] }}]"
                                value="{{ old('break_out.' . $breakTimes[$i]['id'], $breakTimes[$i]['clock_out']) }}">
                        </td>
                        @if ($errors->has('break_in.' . $breakTimes[$i]['id']) || $errors->has('break_out.' . $breakTimes[$i]['id']))
                            <td class="msg">
                                {{ $errors->first('break_in.' . $breakTimes[$i]['id']) }}
                                {{ $errors->first('break_out.' . $breakTimes[$i]['id']) }}
                            </td>
                        @endif
                    </tr>
                @endfor
                <tr class="admin__row">
                    <th class="admin__label">休憩{{ count($breakTimes) + 1 }}</th>
                    <td class="admin__data">
                        <input type="time" name="break_in[0]" value="{{ old('break_in.0') }}">
                    </td>
                    <td class="admin__data">～</td>
                    <td class="admin__data">
                        <input type="time" name="break_out[0]" value="{{ old('break_out.0') }}">
                    </td>
                    @if ($errors->has('break_in.0') || $errors->has('break_out.0'))
                        <td class="msg">
                            {{ $errors->first('break_in.0') }}
                            {{ $errors->first('break_out.0') }}
                        </td>
                    @endif
                </tr>
            @endif
            <tr class="admin__row">
                <th class="admin__label">備考</th>
                <td class="admin__data">
                    <textarea name="note">{{ old('note', $attendanceApplication?->note) }}</textarea>
                </td>
                @error('note')
                    <td class="msg">
                        {{ $message }}
                    </td>
                @enderror
            </tr>
        </table>
        <div class="">
            @if ($mode === 'approve')
                @if ($attendanceApplication->isApproved())
                    承認済み
                    {{-- <button class="">承認済み</button> --}}
                @elseif(!$attendanceApplication->isApproved())
                    <button class="">承認</button>
                @endif
            @elseif ($mode === 'show')
                @if (is_null($attendanceApplication) || $attendanceApplication->isApproved())
                    <button class="">修正</button>
                @elseif(!$attendanceApplication->isApproved())
                    {{-- <button class="">承認</button> --}}
                    *承認待ちのため修正はできません。
                @endif
            @endif
        </div>
        </form>
    </div><!-- admin-->
@endsection
