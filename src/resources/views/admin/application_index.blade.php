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
        <div>
            <nav class="">
                <ul class="">
                    <li class="">
                        <a class=""
                            href="{{ route('application.index', ['tab' => App\Enums\AttendanceStatus::PENDING->value]) }}">承認待ち</a>
                    </li>
                    <li class="">
                        <a class=""
                            href="{{ route('application.index', ['tab' => App\Enums\AttendanceStatus::APPROVED->value]) }}">承認済み</a>
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
            @if (!empty($applications))
                @foreach ($applications as $id => $application)
                    <tr class="admin__row">
                        <td class="admin__data">{{ $application->status }}</td>
                        <td class="admin__data">{{ $application->attendance->user->name }}</td>
                        <td class="admin__data">{{ $application->attendance->work_date }}</td>
                        <td class="admin__data">{{ $application->note }}</td>
                        <td class="admin__data">{{ $application->applied_at }}</td>
                        <td class="admin__data">
                            <a class="admin__detail-btn"
                                href="{{ route('admin.show', ['id' => $application->attendance->id]) }}">詳細
                            </a>
                        </td>
                    </tr>
                @endforeach
            @endif
        </table>
    </div><!-- application -->
@endsection
