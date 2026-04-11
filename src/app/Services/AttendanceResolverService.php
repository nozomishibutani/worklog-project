<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use Carbon\Carbon;
use App\Models\AttendanceChange;
use App\Models\Attendance;
use App\Enums\Role;
use App\Enums\AttendanceStatus;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use App\Enums\ApprovalStatus;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Enums\Guard;
use App\Models\AttendanceApproval;

use function Symfony\Component\Clock\now;

class AttendanceResolverService
{
    protected AttendanceRepository $attendanceRepository;
    protected AttendanceFormatterService $attendanceFormatterService;

    public function __construct(AttendanceFormatterService $attendanceFormatterService, AttendanceRepository $attendanceRepository)
    {
        $this->attendanceFormatterService = $attendanceFormatterService;
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * 指定日の勤怠の最新状態を取得
     */
    public function getCurrentAttendance($attendanceId, $attendanceChangeId): ?array
    {
        if ($attendanceChangeId) {
            $change = AttendanceChange::with([
                'breakTimes',
                'attendanceApproval.breakTimes'
                ])->find($attendanceChangeId);

            if ($change->attendanceApproval) {
                return [
                    'currentAttendanceStatus' => ApprovalStatus::APPROVED->value,
                    'currentAttendance' => $change->attendanceApproval,
                ];
            }
            return [
                'currentAttendanceStatus' => ApprovalStatus::PENDING->value,
                'currentAttendance' => $change,
            ];
        }

        if ($attendanceId) {
            $attendance = Attendance::with([
                'breakTimes',
                'latestAttendanceChange.breakTimes',
                'latestAttendanceChange.attendanceApproval.breakTimes',
            ])->find($attendanceId);

            $change = $attendance->latestAttendanceChange;

            if ($change && $change->attendanceApproval) {
                return [
                    'currentAttendanceStatus' => ApprovalStatus::APPROVED->value,
                    'currentAttendance' => $change->attendanceApproval,
                ];
            }

            if ($change) {
                return [
                    'currentAttendanceStatus' => ApprovalStatus::PENDING->value,
                    'currentAttendance' => $change,
                ];
            }

            return [
                'currentAttendanceStatus' => null,
                'currentAttendance' => $attendance,
            ];
        }

        return [
            'currentAttendanceStatus' => null,
            'currentAttendance' => null,
        ];

    }

    /**
     * ユーザーの打刻状態を取得
     */
    public function getUserAttendanceStatus($date): array
    {
        $user = Auth::user();

        /** @var \App\Models\User $user */
        $attendance = $user->attendances()->where('work_date', $date)->first();

        if (is_null($attendance)) {
            // 勤務開始
            return [
                'attendanceStatus' => AttendanceStatus::OFF,
                'attendance' => $attendance,
            ];
        }

        if (!is_null($attendance->clock_out)) {
            // 退勤済み
            return [
                'attendanceStatus' => AttendanceStatus::OFF_DUTY,
                'attendance' => $attendance,
            ];
        }

        if (!is_null($attendance->clock_in)) {
            $breakTime = $attendance->breakTimes()->latest('clock_in')->first();
            if (is_null($breakTime)) {
                // 休憩入もしくは退勤を選択
                $attendanceStatus = AttendanceStatus::ON_DUTY;
            } elseif (is_null($breakTime['clock_out'])) {
                // 休憩中
                $attendanceStatus = AttendanceStatus::ON_BREAK;
            } elseif (!is_null($breakTime['clock_out'])) {
                // 休憩戻 → 休憩入もしくは退勤を選択
                $attendanceStatus = AttendanceStatus::ON_DUTY;
            }
        }
        return [
            'attendanceStatus' => $attendanceStatus,
            'attendance' => $attendance,
            ];
    }
}
