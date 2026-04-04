@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title></title>
@endsection

@section('content')
    <div class="user">
        @if (session('alert'))
            <div class="alert {{ session('alert-type', 'alert-success') }}">
                <p>{{ session('alert') }}</p>
            </div>
        @endif
        <div class="">{{ $attendanceStatus->label() }}</div>
        <div class="">{{ $day }}</div>
        <div class="">{{ $time }}</div>
        <div class="">
            <form class="" action="{{ route('register') }}" method="post">
                {{-- hidden --}}
                <input type="hidden" name="attendance_id" value="{{ $attendance?->id }}">
                @csrf
                @if ($attendanceStatus->value === App\Enums\attendanceStatus::OFF->value)
                    <button name="action" value="{{ App\Enums\attendanceStatus::ON_DUTY->value }}">出勤</button>
                @elseif($attendanceStatus->value === App\Enums\attendanceStatus::ON_DUTY->value)
                    <button name="action" value="{{ App\Enums\attendanceStatus::OFF->value }}">退勤</button>
                    <button name="action" value="{{ App\Enums\attendanceStatus::ON_BREAK->value }}">休憩入</button>
                @elseif($attendanceStatus->value === App\Enums\attendanceStatus::ON_BREAK->value)
                    <button name="action" value="{{ App\Enums\attendanceStatus::OFF_BREAK->value }}">休憩戻</button>
                @elseif($attendanceStatus->value === App\Enums\attendanceStatus::OFF_DUTY->value)
                    お疲れさまでした。
                @endif
            </form>
        </div>
    </div>
@endsection
