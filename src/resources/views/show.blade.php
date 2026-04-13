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
        @if (session('alert'))
            <div class="alert {{ session('alert-type', 'alert-success') }}">
                <p>{{ session('alert') }}</p>
            </div>
        @endif
        @if (session('login_form') === App\Enums\LoginForm::ADMIN->value)
            <form class="" action="{{ route('admin.update') }}" method="POST" novalidate>
            @elseif (session('login_form') === App\Enums\LoginForm::GENERAL->value)
                <form class="" action="{{ route('update') }}" method="POST" novalidate>
        @endif
        @csrf
        {{-- hidden --}}
        <input type="hidden" name="user_id" value="{{ $workTimes['userId'] }}">
        <input type="hidden" name="attendance_id" value="{{ $attendanceId }}">
        <input type="hidden" name="current_attendance_status" value="{{ $currentAttendanceStatus }}">
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
            {{-- 修正可能 --}}
            @if ($currentAttendanceStatus === App\Enums\ApprovalStatus::APPROVED->value || is_null($currentAttendanceStatus))
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
                    {{-- 休憩あり --}}
                    @for ($i = 0; $i < count($breakTimes); $i++)
                        <tr class="admin__row">
                            <th class="admin__label">休憩{{ $i > 0 ? $i + 1 : '' }}</th>
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
                {{-- 休憩分＋１のレコード表示なし --}}
            @elseif($currentAttendanceStatus === App\Enums\ApprovalStatus::PENDING->value)
                @for ($i = 0; $i < count($breakTimes); $i++)
                    <tr class="admin__row">
                        <th class="admin__label">休憩{{ $i > 0 ? $i + 1 : '' }}</th>
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
            @endif
            <tr class="admin__row">
                <th class="admin__label">備考</th>
                <td class="admin__data">
                    <textarea name="note">{{ old('note', $note) }}</textarea>
                </td>
                @error('note')
                    <td class="msg">
                        {{ $message }}
                    </td>
                @enderror
            </tr>
        </table>
        <div class="">
            @if (is_null($currentAttendanceStatus) || $currentAttendanceStatus === App\Enums\ApprovalStatus::APPROVED->value)
                <button class="">修正!!</button>
            @else
                *承認待ちのため修正はできません。
            @endif
        </div>
        </form>
    </div><!-- admin-->
@endsection
