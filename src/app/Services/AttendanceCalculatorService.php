<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceHistory;
use Illuminate\Support\Facades\Auth;

use function Symfony\Component\VarDumper\Dumper\esc;

class AttendanceCalculatorService
{
    protected AttendanceRepository $attendanceRepository;
    protected AttendanceFormatterService $attendanceFormatterService;

    public function __construct(AttendanceFormatterService $attendanceFormatterService, AttendanceRepository $attendanceRepository)
    {
        $this->attendanceFormatterService = $attendanceFormatterService;
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * 特定日のユーザーの勤怠を取得
     */
    public function getUserDailyAttendances($date): array
    {
        $attendances = Attendance::with(['user', 'breakTimes'])
                                    ->where('work_date', $date)
                                    ->get();

        $workTimes = [];
        $breakTimes = [];
        $baseList = [];

        $invalidWorkUsers = [];
        $invalidBreakUsers = [];

        $workMinutes = [];
        $breakMinutes = [];

        foreach ($attendances as $attendance) {
            $userId = $attendance->user->id;

            $clockIn  = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;

            $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;

            // --- base ---
            $baseList[$userId] = [
                'attendance_id' => $attendance->id,
                'name'      => $attendance->user->name,
                'clock_in'  => $clockIn ? $clockIn->format('H:i') : null,
                'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
            ];

            // --- 休憩 ---
            $breakDiff = 0;
            $hasBreak = false;

            foreach ($attendance->breakTimes as $breakTime) {
                if (!$breakTime->clock_out) {
                    $invalidBreakUsers[$userId] = true;
                    break;
                }

                $hasBreak = true;

                $breakIn  = Carbon::parse($breakTime->clock_in);
                $breakOut = Carbon::parse($breakTime->clock_out);
                $breakDiff += $breakIn->diffInMinutes($breakOut);
            }

            if (!isset($invalidBreakUsers[$userId]) && $hasBreak) {
                $breakMinutes[$userId] = $breakDiff;
            }

            // --- 労働 ---
            if (!$clockOut || isset($invalidBreakUsers[$userId])) {
                $invalidWorkUsers[$userId] = true;
                continue;
            }

            $workMinutes[$userId] = $clockIn->diffInMinutes($clockOut) - $breakDiff;

            // 休憩なし
            if (!$hasBreak) {
                $breakMinutes[$userId] = 0;
            }
        }

        // --- 整形 ---
        foreach ($baseList as $userId => $baseData) {

            [$workTimes[$userId], $breakTimes[$userId]] =
                $this->attendanceFormatterService->formatAttendanceRow(
                    $baseData,
                    $workMinutes[$userId] ?? null,
                    $breakMinutes[$userId] ?? null,
                    isset($invalidWorkUsers[$userId]),
                    isset($invalidBreakUsers[$userId])
                );
        }

        ksort($workTimes);
        ksort($breakTimes);

        return [
            'workTimes'  => $workTimes,
            'breakTimes' => $breakTimes,
        ];
    }

    /**
     * 指定ユーザーの日時勤怠詳細を取得(勤怠レコードが存在する場合)
     */
    public function getUserDailyAttendance(Attendance|AttendanceHistory $attendance): array
    {

        $workTimes = [
            'attendanceId' => null,
            'clock_in' => null,
            'clock_out' => null,
        ];
        $breakTimes = [];

        // ---  労働時間 ---
        $clockIn  = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;
        $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;
        $date = $attendance->work_date;
        $workDate = [
                        'year'  => $date->year,
                        'month' => $date->month,
                        'day'   => $date->day,
                    ];

        $tmp = [
                'attendanceId' => $attendance->id,
                'userId' => $attendance->user_id,
                'name' => $attendance->user?->name ? $attendance->user->name : $attendance->attendanceApplication->attendance->user->name,
                'clock_in' => $clockIn ? $clockIn->format('H:i') : null,
                'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
                ];
        $workTimes = array_merge($workTimes, $tmp);

        // ---  休憩時間 ---
        $breakTimes = $attendance->breakTimes ?? $attendance->breakTimeHistories;
        //if (!is_null($attendance->breakTimes)) {
            foreach ($breakTimes as $breakTime) {
                $clockIn  = $breakTime->clock_in ? Carbon::parse($breakTime->clock_in) : null;
                $clockOut = $breakTime->clock_out ? Carbon::parse($breakTime->clock_out) : null;
                $breakTimes[] = [
                    'id' => $breakTime->id,
                    'clock_in' => $clockIn ? $clockIn->format('H:i') : null,
                    'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
                ];
            }
        // } elseif (!is_null($attendance->breakTimeHistories)) {
        //     foreach ($attendance->breakTimeHistories as $breakTime) {
        //         $clockIn  = $breakTime->clock_in ? Carbon::parse($breakTime->clock_in) : null;
        //         $clockOut = $breakTime->clock_out ? Carbon::parse($breakTime->clock_out) : null;
        //         $breakTimes[] = [
        //             'id' => $breakTime->id,
        //             'clock_in' => $clockIn ? $clockIn->format('H:i') : null,
        //             'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
        //         ];
        //     }
        // }
        $sorted = collect($breakTimes)->sortBy('clock_in')->values()->all();

        return [
            'userId' => $attendance->user_id,
            'workTimes' => $workTimes,
            'breakTimes' => $sorted,
            'workDate' => $workDate,
        ];
    }

    /**
     * 指定ユーザーの月次勤怠を取得
     */
    public function getUserMonthlyAttendances($userId, Carbon $startOfMonth): array
    {
        $start = $startOfMonth;
        $end   = $startOfMonth->copy()->endOfMonth();

        $attendances = $this->attendanceRepository->getUserMonthlyAttendances($userId, $start, $end);

        $workTimes = [];
        $breakTimes = [];

        $invalidWorkUsers = [];
        $invalidBreakUsers = [];

        $workMinutes = [];
        $breakMinutes = [];

        $baseList = [];

        foreach ($attendances as $attendance) {

            $workDate = Carbon::parse($attendance->work_date);
            $key = $workDate->format('Ymd');

            $clockIn  = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;
            $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;

            // --- base ---
            $baseList[$key] = [
                'attendance_id' => $attendance->id,
                'clock_in'  => $clockIn ? $clockIn->format('H:i') : null,
                'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
            ];

            // --- 休憩 ---
            $breakDiff = 0;
            $hasBreak = false;

            foreach ($attendance->breakTimes as $breakTime) {
                if (!$breakTime->clock_out) {
                    $invalidBreakUsers[$key] = true;
                    break;
                }

                $hasBreak = true;

                $breakIn  = Carbon::parse($breakTime->clock_in);
                $breakOut = Carbon::parse($breakTime->clock_out);
                $breakDiff += $breakIn->diffInMinutes($breakOut);
            }

            if (!isset($invalidBreakUsers[$key]) && $hasBreak) {
                $breakMinutes[$key] = $breakDiff;
            }

            // --- 労働 ---
            if (!$clockOut || isset($invalidBreakUsers[$key])) {
                $invalidWorkUsers[$key] = true;
                continue;
            }

            $workMinutes[$key] = $clockIn->diffInMinutes($clockOut) - $breakDiff;

            if (!$hasBreak) {
                $breakMinutes[$key] = 0;
            }
        }

        // --- 整形 ---
        foreach ($baseList as $key => $base) {

            [$workTimes[$key], $breakTimes[$key]] =
                $this->attendanceFormatterService->formatAttendanceRow(
                    $base,
                    $workMinutes[$key] ?? null,
                    $breakMinutes[$key] ?? null,
                    isset($invalidWorkUsers[$key]),
                    isset($invalidBreakUsers[$key])
                );
        }

        // 空日付埋める
        $workTimes = $this->attendanceFormatterService->createMonthlyEmptyRecords($start, $end, $workTimes);

        ksort($workTimes);
        ksort($breakTimes);

        $user = User::find($userId);
        return [
            'name'       => $user->name,
            'workTimes'  => $workTimes,
            'breakTimes' => $breakTimes,
        ];
    }
    /**
     * ユーザーの勤怠ステータスを取得
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
