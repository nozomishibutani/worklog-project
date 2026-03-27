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
            @csrf
            {{-- hidden --}}
            <input type="hidden" name="user_id" value="{{ $userId }}">
            <input type="hidden" name="attendance_id" value="{{ $workTimes['attendanceId'] }}">
            <table class="admin__table">
                <tr class="admin__row">
                    <th class="admin__label">名前</th>
                    <td class="admin__data">{{ $workTimes['name'] }}</td>
                </tr>
                <tr class="admin__row">
                    <th class="admin__label">日付</th>
                    <td class="admin__data">{{ $date['year'] }}年</td>
                    <td class="admin__data">{{ $date['month'] }}月{{ $date['day'] }}日</td>
                </tr>
                <tr class="admin__row">
                    <th class="admin__label">出勤・退勤</th>
                    <td class="admin__data">{{ $workTimes['clock_in'] }}
                        <input type="time" name="work_in" value="{{ old('work_in', $workTimes['clock_in']) }}">
                    </td>
                    <td class="admin__data">～</td>
                    <td class="admin__data">
                        <input type="time" name="work_out" value="{{ old('work_out', $workTimes['clock_out']) }}">
                    </td>
                </tr>
                {{-- 休憩なし --}}
                @if ($breakTimeCount == 0)
                    <tr class="admin__row">
                        <th class="admin__label">休憩</th>
                        <td class="admin__data">
                            <input type="time" name="break_in" value="{{ old('break_in') }}">
                        </td>
                        <td class="admin__data">～</td>
                        <td class="admin__data">
                            <input type="time" name="break_out" value="{{ old('break_out') }}">
                        </td>
                    </tr>
                @elseif ($breakTimeCount > 1)
                    {{-- 休憩がある場合 --}}
                    @for ($i = 1; $i <= $breakTimeCount; $i++)
                        <tr class="admin__row">
                            <th class="admin__label">休憩{{ $i > 1 ? $i : '' }}</th>
                            <td class="admin__data">
                                <input type="time" name="break_in"
                                    value="{{ old('break_in', $breakTimes[$i]['clock_in']) }}">
                            </td>
                            <td class="admin__data">～</td>
                            <td class="admin__data">
                                <input type="time" name="break_out"
                                    value="{{ old('break_out', $breakTimes[$i]['clock_out']) }}">
                            </td>
                        </tr>
                    @endfor
                    <tr class="admin__row">
                        <th class="admin__label">休憩{{ $breakTimeCount + 1 }}</th>
                        <td class="admin__data">
                            <input type="time" name="break_in" value="{{ old('break_in') }}">
                        </td>
                        <td class="admin__data">～</td>
                        <td class="admin__data">
                            <input type="time" name="break_out" value="{{ old('break_out') }}">
                        </td>
                    </tr>
                @endif
                <tr class="admin__row">
                    <th class="admin__label">備考</th>
                    <td class="admin__data">
                        <textarea name="note">{{ old('note', $workTimes['note']) }}</textarea>
                    </td>
                </tr>
            </table>
            <div class="">
                @if ($workTimes['status'] == App\Enums\AttendanceStatus::APPROVED)
                    <button class="">修正済み</button>
                @elseif($workTimes['status'] == App\Enums\AttendanceStatus::PENDING)
                    <button class="">承認</button>
                @else
                    <button class="">修正</button>
                @endif
            </div>
        </form>
    </div><!-- admin-->
@endsection
