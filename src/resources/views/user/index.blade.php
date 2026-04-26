@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/user/index.css') }}">
@endsection

@section('title')
    <title>勤怠登録画面_{{ $attendanceStatus->label() }}</title>
@endsection

@section('content')
    <div class="general">
        @if (session('alert'))
            <div class="alert general__alert {{ session('alert-type', 'alert--success') }}">
                <p>{{ session('alert') }}</p>
            </div>
        @endif
        <span class="general__label general__label--status">{{ $attendanceStatus->label() }}</span>
        <span class="general__label general__label--date">{{ $date }}</span>
        <span class="general__label general__label--time">{{ $time }}</span>
        <form action="{{ route('log') }}" method="POST">
            @csrf
            {{-- hidden --}}
            <input type="hidden" name="attendance_id" value="{{ $attendance?->id }}">
            <div class="general__btn-box">
                @if ($attendanceStatus->value === App\Enums\attendanceStatus::OFF->value)
                    <button class="btn general__btn" name="action"
                        value="{{ App\Enums\attendanceStatus::ON_DUTY->value }}">出勤</button>
                @elseif($attendanceStatus->value === App\Enums\attendanceStatus::ON_DUTY->value)
                    <button class="btn general__btn general__btn--attendance-off" name="action" value="{{ App\Enums\attendanceStatus::OFF->value }}">退勤</button>
                    <button class="btn general__btn--break" name="action"
                        value="{{ App\Enums\attendanceStatus::ON_BREAK->value }}">休憩入</button>
                @elseif($attendanceStatus->value === App\Enums\attendanceStatus::ON_BREAK->value)
                    <button class="btn general__btn--break" name="action"
                        value="{{ App\Enums\attendanceStatus::OFF_BREAK->value }}">休憩戻</button>
                @elseif($attendanceStatus->value === App\Enums\attendanceStatus::OFF_DUTY->value)
                    <span class="general__msg--attendance-off">お疲れ様でした。</span>
                @endif
            </div>
        </form>
    </div>
@endsection
