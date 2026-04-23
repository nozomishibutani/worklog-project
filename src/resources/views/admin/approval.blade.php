@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/approval.css') }}">
@endsection

@section('title')
    <title>修正申請承認画面</title>
@endsection

@section('content')
    <div class="approval">
        <h1 class="approval-ttl">勤怠詳細</h1>
        @if (session('alert'))
            <div class="alert {{ session('alert-type', 'alert--success') }}">
                <p>{{ session('alert') }}</p>
            </div>
        @endif
        {{-- <form action="{{ route('admin.approve', ['attendance_correct_request_id' => $attendanceChangeId]) }}" method="post"
            novalidate> --}}
        <form action="{{ route('admin.approve') }}" method="POST" novalidate>
            @csrf
            {{-- hidden --}}
            <input type="hidden" name="year" value="{{ $workDate['year'] }}">
            <input type="hidden" name="month" value="{{ $workDate['month'] }}">
            <input type="hidden" name="day" value="{{ $workDate['day'] }}">
            <input type="hidden" name="attendance_change_id" value="{{ $attendanceChangeId }}">
            <table class="approval__table">
                <tr class="approval__row">
                    <th class="approval__label">名前</th>
                    <td class="approval__data"><span class="approval__data-name">{{ $workTimes['name'] }}</span></td>
                </tr>
                <tr class="approval__row">
                    <th class="approval__label">日付</th>
                    <td class="approval__data">
                        <div class="approval__data-box">
                            <span>{{ $workDate['year'] }}年</span>
                            <span class="approval__data-span">
                                {{ $workDate['month'] }}月
                                {{ $workDate['day'] }}日
                            </span>
                        </div>
                    </td>
                </tr>
                <tr class="approval__row">
                    <th class="approval__label">出勤・退勤</th>
                    <td class="approval__data">
                        <div class="approval__data-box">
                            @if (!is_null($workTimes['clock_in']))
                                <span>{{ $workTimes['clock_in'] }}</span>
                                <span>～</span>
                                <span>{{ $workTimes['clock_out'] }}</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @if (count($breakTimes) == 0)
                    {{-- 休憩なし --}}
                    <tr class="approval__row">
                        <th class="approval__label">休憩</th>
                        <td class="approval__data">
                            <div class="approval__data-box">
                            </div>
                        </td>
                    </tr>
                @elseif (count($breakTimes) > 0)
                    {{-- 休憩あり --}}
                    @for ($i = 0; $i < count($breakTimes); $i++)
                        <tr class="approval__row">
                            <th class="approval__label">休憩{{ $i > 0 ? $i + 1 : '' }}</th>
                            <td class="approval__data">
                                <div class="approval__data-box">
                                    <span>{{ $breakTimes[$i]['clock_in'] }}</span>
                                    <span>～</span>
                                    <span>{{ $breakTimes[$i]['clock_out'] }}</span>
                                </div>
                            </td>
                        </tr>
                    @endfor
                    <tr class="approval__row">
                        <th class="approval__label">休憩{{ count($breakTimes) + 1 }}</th>
                        <td class="approval__data">
                            <div class="approval__data-box">
                            </div>
                        </td>
                    </tr>
                @endif
                <tr class="approval__row">
                    <th class="approval__label">備考</th>
                    <td class="approval__data">
                        <p class="approval__data-textarea">{{ $note }}</p>
                    </td>
                </tr>
            </table>
            <div class="approval__btn-box">
                @if ($currentAttendanceStatus === App\Enums\ApprovalStatus::APPROVED->value)
                    <button class="btn" disabled>承認済み</button>
                @elseif($currentAttendanceStatus === App\Enums\ApprovalStatus::PENDING->value)
                    <button class="btn"> 承認</button>
                @endif
            </div>
        </form>
    </div><!-- approval -->
@endsection
