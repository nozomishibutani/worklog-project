@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/.css') }}">
@endsection

@section('title')
    <title>申請一覧</title>
@endsection

@section('content')
    <div class="application">
        <h1>申請一覧</h1>
        @if (session('alert'))
            <div class="alert {{ session('alert-type', 'alert-success') }}">
                <p>{{ session('alert') }}</p>
            </div>
        @endif
        <div>
            <nav class="">
                <ul class="">
                    <li class="">
                        <a class=""
                            href="{{ route('application.index', ['mode' => App\Enums\ApprovalStatus::PENDING->value]) }}">承認待ち</a>
                    </li>
                    <li class="">
                        <a class=""
                            href="{{ route('application.index', ['mode' => App\Enums\ApprovalStatus::APPROVED->value]) }}">承認済み</a>
                    </li>
                </ul>
            </nav>
        </div>
        <table class="admin__table">
            <tr class="admin__row">
                <th class="admin__label">状態</th>
                <th class="admin__label">名前</th>
                <th class="admin__label">対象日時</th>
                <th class="admin__label">申請理由</th>
                <th class="admin__label">申請日時</th>
                <th class="admin__label">詳細</th>
            </tr>
            @if (!empty($attendances))
                @if (session('login_form') === App\Enums\LoginForm::ADMIN->value)
                    @foreach ($attendances as $id => $attendance)
                        <tr class="admin__row">
                            <td class="admin__data">{{ $approvalStatus->label() }}</td>
                            <td class="admin__data">{{ $attendance->user->name }}</td>
                            <td class="admin__data">{{ $attendance->work_date->format('Y/m/d') }}</td>
                            <td class="admin__data">
                                {{ $attendance->note }}</td>
                            <td class="admin__data">
                                {{ $attendance->applied_at?->format('Y/m/d') ?? $attendance->attendanceChange->applied_at->format('Y/m/d') }}
                            </td>
                            <td class="admin__data">
                                <a class="admin__detail-btn"
                                    href="{{ route('admin.approval.show', ['attendance_correct_request_id' => $attendance->AttendanceChange?->id ?? $attendance->id]) }}">詳細
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @elseif (session('login_form') === App\Enums\LoginForm::GENERAL->value)
                    @foreach ($attendances as $id => $attendance)
                        <tr class="admin__row">
                            <td class="admin__data">{{ $approvalStatus->label() }}</td>
                            <td class="admin__data">{{ $attendance->user->name }}</td>
                            <td class="admin__data">{{ $attendance->work_date->format('Y/m/d') }}</td>
                            <td class="admin__data">
                                {{ $attendance->latestAttendanceChange->note }}
                            </td>
                            <td class="admin__data">
                                {{ $attendance->latestAttendanceChange->applied_at->format('Y/m/d') }}
                            </td>
                            <td class="admin__data">
                                <a class="admin__detail-btn" href="{{ route('show', ['id' => $attendance->id]) }}">詳細
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endif
        </table>
    </div><!-- application -->
@endsection
