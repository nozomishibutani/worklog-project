@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/application_index.css') }}">
@endsection

@section('title')
    <title>申請一覧</title>
@endsection

@section('content')
    <div class="application">
        <h1 class="application-ttl">申請一覧</h1>
        @if (session('alert'))
            <div class="alert {{ session('alert-type', 'alert-success') }}">
                <p>{{ session('alert') }}</p>
            </div>
        @endif
        <nav class="application__nav">
            <ul class="application__list">
                <li class="application__item">
                    <a @class([
                        'link',
                        'application__link',
                        'application__link--select' =>
                            $approvalStatus === App\Enums\ApprovalStatus::PENDING,
                    ])
                        href="{{ route('application.index', ['mode' => App\Enums\ApprovalStatus::PENDING->value]) }}">承認待ち</a>
                </li>
                <li class="application__item">
                    <a @class([
                        'link',
                        'application__link',
                        'application__link--select' =>
                            $approvalStatus === App\Enums\ApprovalStatus::APPROVED,
                    ])
                        href="{{ route('application.index', ['mode' => App\Enums\ApprovalStatus::APPROVED->value]) }}">承認済み</a>
                </li>
            </ul>
        </nav>
        <table class="application__table">
            <tr class="application__row">
                <th class="application__label">状態</th>
                <th class="application__label">名前</th>
                <th class="application__label">対象日時</th>
                <th class="application__label">申請理由</th>
                <th class="application__label">申請日時</th>
                <th class="application__label">詳細</th>
            </tr>
            @if (!empty($attendances))
                @if (session('login_form') === App\Enums\LoginForm::ADMIN->value)
                    @foreach ($attendances as $id => $attendance)
                        <tr class="application__row">
                            <td class="application__data">{{ $approvalStatus->label() }}</td>
                            <td class="application__data">{{ $attendance->user->name }}</td>
                            <td class="application__data">{{ $attendance->work_date->format('Y/m/d') }}</td>
                            <td class="application__data">{{ $attendance->note }}</td>
                            <td class="application__data">
                                {{ $attendance->applied_at?->format('Y/m/d') ?? $attendance->attendanceChange->applied_at->format('Y/m/d') }}
                            </td>
                            <td class="application__data">
                                <a class="link application__link"
                                    href="{{ route('admin.approval.show', ['attendance_correct_request_id' => $attendance->AttendanceChange?->id ?? $attendance->id]) }}">詳細
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @elseif (session('login_form') === App\Enums\LoginForm::GENERAL->value)
                    @foreach ($attendances as $id => $attendance)
                        <tr class="application__row">
                            <td class="application__data">{{ $approvalStatus->label() }}</td>
                            <td class="application__data">{{ $attendance->user->name }}</td>
                            <td class="application__data">{{ $attendance->work_date->format('Y/m/d') }}</td>
                            <td class="application__data">
                                {{ $attendance->latestAttendanceChange->note }}
                            </td>
                            <td class="application__data">
                                {{ $attendance->latestAttendanceChange->applied_at->format('Y/m/d') }}
                            </td>
                            <td class="application__data">
                                <a class="link application__link" href="{{ route('show', ['id' => $attendance->id]) }}">詳細
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endif
        </table>
    </div><!-- application -->
@endsection
