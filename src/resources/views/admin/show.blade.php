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

        <form class="" action="{{ route('admin.update') }}" method="post" novalidate>
            @csrf
            {{-- hidden --}}
            <input type="hidden" name="user_id" value="{{ $userId }}">
            <input type="hidden" name="attendance_id" value="{{ $workTimes['attendanceId'] }}">
            <input type="hidden" name="year" value="{{ $workDate['year'] }}">
            <input type="hidden" name="month" value="{{ $workDate['month'] }}">
            <input type="hidden" name="day" value="{{ $workDate['day'] }}">
            <table class="admin__table">
                <tr class="admin__row">
                    <th class="admin__label">名前</th>
                    <td class="admin__data">{{ $workTimes['name'] }}</td>
                </tr>
                <tr class="admin__row">
                    <th class="admin__label">日付</th>
                    <td class="admin__data">{{ $workDate['year'] }}年</td>
                    <td class="admin__data">{{ $workDate['month'] }}月{{ $workDate['day'] }}日</td>
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
                    @error('work_in')
                        <td class="msg">
                            {{ $message }}
                        </td>
                    @enderror
                    @error('work_out')
                        <td class="msg">
                            {{ $message }}
                        </td>
                    @enderror
                </tr>
                {{-- 休憩なし --}}
                @if ($breakTimeCount == 0)
                    <tr class="admin__row">
                        <th class="admin__label">休憩</th>
                        <td class="admin__data">
                            <input type="time" name="break_in[{{ App\Enums\AttendanceStatus::DRAFT->value }}]"
                                value="{{ old('break_in.' . App\Enums\AttendanceStatus::DRAFT->value) }}">
                        </td>
                        <td class="admin__data">～</td>
                        <td class="admin__data">
                            <input type="time" name="break_out[{{ App\Enums\AttendanceStatus::DRAFT->value }}]"
                                value="{{ old('break_out.' . App\Enums\AttendanceStatus::DRAFT->value) }}">
                        </td>
                        @error('break_in.' . App\Enums\AttendanceStatus::DRAFT->value)
                            <td class="msg">
                                {{ $message }}
                            </td>
                        @enderror
                        @error('break_out.' . App\Enums\AttendanceStatus::DRAFT->value)
                            <td class="msg">
                                {{ $message }}
                            </td>
                        @enderror
                    </tr>
                @elseif ($breakTimeCount > 0)
                    {{-- 休憩がある場合 --}}
                    @for ($i = 1; $i <= $breakTimeCount; $i++)
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
                            @error('break_in.' . $breakTimes[$i]['id'])
                                <td class="msg">
                                    {{ $message }}
                                </td>
                            @enderror
                            @error('break_out.' . $breakTimes[$i]['id'])
                                <td class="msg">
                                    {{ $message }}
                                </td>
                            @enderror
                        </tr>
                    @endfor
                    <tr class="admin__row">
                        <th class="admin__label">休憩{{ $breakTimeCount + 1 }}</th>
                        <td class="admin__data">
                            <input type="time" name="break_in[{{ App\Enums\AttendanceStatus::DRAFT->value }}]"
                                value="{{ old('break_in.' . App\Enums\AttendanceStatus::DRAFT->value) }}">
                        </td>
                        <td class="admin__data">～</td>
                        <td class="admin__data">
                            <input type="time" name="break_out[{{ App\Enums\AttendanceStatus::DRAFT->value }}]"
                                value="{{ old('break_out.' . App\Enums\AttendanceStatus::DRAFT->value) }}">
                        </td>
                        @error('break_in.' . App\Enums\AttendanceStatus::DRAFT->value)
                            <td class="msg">
                                {{ $message }}
                            </td>
                        @enderror
                        @error('break_out.' . App\Enums\AttendanceStatus::DRAFT->value)
                            <td class="msg">
                                {{ $message }}
                            </td>
                        @enderror
                    </tr>
                @endif
                <tr class="admin__row">
                    <th class="admin__label">備考</th>
                    <td class="admin__data">
                        <textarea name="note">{{ old('note', $workTimes['note']) }}</textarea>
                    </td>
                    @error('note')
                        <td class="msg">
                            {{ $message }}
                        </td>
                    @enderror
                </tr>
            </table>
            <div class="">
                @if ($workTimes['status'] == App\Enums\AttendanceStatus::APPROVED->value)
                    <button class="">承認済み</button>
                @elseif($workTimes['status'] == App\Enums\AttendanceStatus::PENDING->value)
                    <button class="">承認</button>
                @else
                    <button class="">修正</button>
                @endif
            </div>
        </form>
    </div><!-- admin-->
@endsection
