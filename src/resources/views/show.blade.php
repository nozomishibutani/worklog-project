@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('title')
    <title>勤怠詳細画面</title>
@endsection

@section('content')
    <div class="show">
        <h1 class="show-ttl">勤怠詳細</h1>
        @if (session('alert'))
            <div class="alert {{ session('alert-type', 'alert--success') }}">
                <p>{{ session('alert') }}</p>
            </div>
        @endif
        @if (session('login_form') === App\Enums\LoginForm::ADMIN->value)
            <form action="{{ route('admin.update') }}" method="POST" novalidate>
            @elseif (session('login_form') === App\Enums\LoginForm::GENERAL->value)
                <form action="{{ route('update') }}" method="POST" novalidate>
        @endif
        @csrf
        {{-- hidden --}}
        <input type="hidden" name="user_id" value="{{ $workTimes['userId'] }}">
        <input type="hidden" name="attendance_id" value="{{ $attendanceId }}">
        <input type="hidden" name="current_attendance_status" value="{{ $currentAttendanceStatus }}">
        <input type="hidden" name="year" value="{{ $workDate['year'] }}">
        <input type="hidden" name="month" value="{{ $workDate['month'] }}">
        <input type="hidden" name="day" value="{{ $workDate['day'] }}">

        <table class="show__table">
            @if ($currentAttendanceStatus === App\Enums\ApprovalStatus::APPROVED->value || is_null($currentAttendanceStatus))
                {{-- 修正可能 --}}
                <tr class="show__row">
                    <th class="show__label">名前</th>
                    <td class="show__data"><span class="show__data-name">{{ $workTimes['name'] }}</span></td>
                </tr>
                <tr class="show__row">
                    <th class="show__label">日付</th>
                    <td class="show__data">
                        <div class="show__data-box">
                            <span class="show__data-year">{{ $workDate['year'] }}年</span>
                            <span class="show__data-span show__data-span--approved">
                                {{ $workDate['month'] }}月
                                {{ $workDate['day'] }}日
                            </span>
                        </div>
                    </td>
                </tr>
                <tr class="show__row">
                    <th class="show__label">出勤・退勤</th>
                    <td class="show__data">
                        <div class="show__data-box">
                            <input class="show__data-input" type="time" name="work_in"
                                value="{{ old('work_in', $workTimes['clock_in']) }}">
                            <span>～</span>
                            <input type="time" class="show__data-input" name="work_out"
                                value="{{ old('work_out', $workTimes['clock_out']) }}">
                        </div>
                        @if ($errors->has('work_in') || $errors->has('work_out'))
                            <div class="msg">
                                {{ $errors->first('work_in') }}
                                {{ $errors->first('work_out') }}
                            </div>
                        @endif
                    </td>
                </tr>
                @if (count($breakTimes) == 0)
                    {{-- 休憩なし --}}
                    <tr class="show__row">
                        <th class="show__label">休憩</th>
                        <td class="show__data">
                            <div class="show__data-box">
                                <input type="time" class="show__data-input" name="break_in[0]"
                                    value="{{ old('break_in.0') }}">
                                <span>～</span>
                                <input type="time" class="show__data-input" name="break_out[0]"
                                    value="{{ old('break_out.0') }}">
                            </div>
                            @if ($errors->has('break_in.0') || $errors->has('break_out.0'))
                                <div class="msg">
                                    {{ $errors->first('break_in.0') }}
                                    {{ $errors->first('break_out.0') }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @elseif (count($breakTimes) > 0)
                    {{-- 休憩あり --}}
                    @for ($i = 0; $i < count($breakTimes); $i++)
                        <tr class="show__row">
                            <th class="show__label">休憩</th>
                            <td class="show__data">
                                <div class="show__data-box">
                                    <input type="time" class="show__data-input"
                                        name="break_in[{{ $breakTimes[$i]['id'] }}]"
                                        value="{{ old('break_in.' . $breakTimes[$i]['id'], $breakTimes[$i]['clock_in']) }}">
                                    <span>～</span>
                                    <input class="show__data-input" type="time"
                                        name="break_out[{{ $breakTimes[$i]['id'] }}]"
                                        value="{{ old('break_out.' . $breakTimes[$i]['id'], $breakTimes[$i]['clock_out']) }}">
                                </div>
                                @if ($errors->has('break_in.' . $breakTimes[$i]['id']) || $errors->has('break_out.' . $breakTimes[$i]['id']))
                                    <div class="msg">
                                        {{ $errors->first('break_in.' . $breakTimes[$i]['id']) }}
                                        {{ $errors->first('break_out.' . $breakTimes[$i]['id']) }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endfor
                    <tr class="show__row">
                        <th class="show__label">休憩{{ count($breakTimes) + 1 }}</th>
                        <td class="show__data">
                            <div class="show__data-box">
                                <input class="show__data-input" type="time" name="break_in[0]"
                                    value="{{ old('break_in.0') }}">
                                <span>～</span>
                                <input class="show__data-input" type="time" name="break_out[0]"
                                    value="{{ old('break_out.0') }}">
                            </div>
                            @if ($errors->has('break_in.0') || $errors->has('break_out.0'))
                                <div class="msg">
                                    {{ $errors->first('break_in.0') }}
                                    {{ $errors->first('break_out.0') }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @endif
                <tr class="show__row">
                    <th class="show__label">備考</th>
                    <td class="show__data">
                        <div class="show__data-box">
                            <textarea class="show__data-textarea" name="note">{{ old('note', $note) }}</textarea>
                        </div>
                        @error('note')
                            <div class="msg">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
            @elseif($currentAttendanceStatus === App\Enums\ApprovalStatus::PENDING->value)
                {{-- 修正不可 --}}
                <tr class="show__row">
                    <th class="show__label">名前</th>
                    <td class="show__data"><span>{{ $workTimes['name'] }}</span></td>
                </tr>
                <tr class="show__row">
                    <th class="show__label">日付</th>
                    <td class="show__data">
                        <div class="show__data-box">
                            <span>{{ $workDate['year'] }}年</span>
                            <span class="show__data-span show__data-span--pending">
                                {{ $workDate['month'] }}月
                                {{ $workDate['day'] }}日
                            </span>
                        </div>
                    </td>
                </tr>
                <tr class="show__row">
                    <th class="show__label">出勤・退勤</th>
                    <td class="show__data">
                        <div class="show__data-box">
                            @if (!is_null($workTimes['clock_in']))
                                <span>{{ $workTimes['clock_in'] }}</span>
                                <span>～</span>
                                <span>{{ $workTimes['clock_out'] }}</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @for ($i = 0; $i < count($breakTimes); $i++)
                    <tr class="show__row">
                        <th class="show__label">休憩{{ $i > 0 ? $i + 1 : '' }}</th>
                        <td class="show__data">
                            <div class="show__data-box">
                                <span>{{ $breakTimes[$i]['clock_in'] }}</span>
                                <span>～</span>
                                <span>{{ $breakTimes[$i]['clock_out'] }}</span>
                            </div>
                        </td>
                    </tr>
                @endfor
                <tr class="show__row">
                    <th class="show__label">備考</th>
                    <td class="show__data">
                        <p>{{ $note }}</p>
                    </td>
                </tr>
            @endif
        </table>
        <div class="show__btn-box">
            @if ($currentAttendanceStatus === App\Enums\ApprovalStatus::APPROVED->value || is_null($currentAttendanceStatus))
                <button class="btn">修正</button>
            @else
                <p class="show__msg">*承認待ちのため修正はできません。</p>
            @endif
        </div>
        </form>
    </div><!-- show-->
@endsection
