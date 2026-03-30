<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use Carbon\Carbon;
use App\Models\User;

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
     * 特定日の全ユーザーの勤怠を取得
     */
    public function getAllUserDailyAttendances($date): array
    {
        $users = $this->attendanceRepository->getAllUserDailyAttendances($date);

        $workTimes = [];
        $breakTimes = [];
        $baseList = [];

        $invalidWorkUsers = [];
        $invalidBreakUsers = [];

        $workMinutes = [];
        $breakMinutes = [];

        foreach ($users as $user) {
            $userId = $user->id;

            $attendance = $user->attendances->first();

            // 勤怠なし
            if (!$attendance) {
                $workTimes[$userId] = [
                    'name'      => $user->name,
                    'clock_in'  => null,
                    'clock_out' => null,
                    'hours'     => null,
                    'minutes'   => null,
                    'display_total' => null,
                ];
                continue;
            }

            $clockIn  = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;
            if (!$clockIn) {
                continue;
            }

            $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;

            // --- base ---
            $baseList[$userId] = [
                'name'      => $user->name,
                'clock_in'  => $clockIn->format('H:i'),
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
     * 指定ユーザーの日時勤怠詳細を取得
     */
    public function getUserDailyAttendance($userId, $date): array
    {
        $attendances = $this->attendanceRepository->getUserDailyAttendance($userId, $date);

        $workTimes = [
            'attendanceId' => null,
            'clock_in' => null,
            'clock_out' => null,
            'note' =>  null,
            'status' =>  null,
        ];
        $breakTimes = [];

        if (!$attendances) {
            $user = User::find($userId);
            $workTimes['name'] = $user->name;
            return [
                'workTimes' => $workTimes,
                'breakTimes' => $breakTimes,
            ];
        }

        // ---  労働時間 ---
        $clockIn  = $attendances->clock_in ? Carbon::parse($attendances->clock_in) : null;
        $clockOut = $attendances->clock_out ? Carbon::parse($attendances->clock_out) : null;
        $tmp = [
                'attendanceId' => $attendances->id,
                'name' => $attendances->user->name,
                'clock_in' => $clockIn ? $clockIn->format('H:i') : null,
                'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
                'note' =>  $attendances->note,
                'status' =>  $attendances->status,
                ];
        $workTimes = array_merge($workTimes, $tmp);

        // ---  休憩時間 ---
        foreach ($attendances->breakTimes as $breakTime) {
            $clockIn  = $breakTime->clock_in ? Carbon::parse($breakTime->clock_in) : null;
            $clockOut = $breakTime->clock_out ? Carbon::parse($breakTime->clock_out) : null;
            $breakTimes[] = [
                'id' => $breakTime->id,
                'clock_in' => $clockIn ? $clockIn->format('H:i') : null,
                'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
            ];
        }
        $sorted = collect($breakTimes)->sortBy('clock_in')->values()->all();

        return [
            'workTimes' => $workTimes,
            'breakTimes' => $sorted,
        ];
    }

    /**
     * 指定ユーザーの月次勤怠を取得
     */
    public function getUserMonthlyAttendances($userId, Carbon $date): array
    {
        $start = $date->copy()->startOfMonth();
        $end   = $date->copy()->endOfMonth();

        $attendances = $this->attendanceRepository
            ->getUserMonthlyAttendances($userId, $start, $end);

        $workTimes = [];
        $breakTimes = [];

        $invalidWorkUsers = [];
        $invalidBreakUsers = [];

        $workMinutes = [];
        $breakMinutes = [];

        $baseList = [];

        $user = User::find($userId);
        $name = $user->name;

        foreach ($attendances as $attendance) {

            $workDate = Carbon::parse($attendance->work_date);
            $key = $workDate->format('Ymd');

            $clockIn  = $attendance->clock_in ? Carbon::parse($attendance->clock_in) : null;
            $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out) : null;

            if (!$clockIn) {
                continue;
            }

            // --- base ---
            $baseList[$key] = [
                'clock_in'  => $clockIn->format('H:i'),
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

        // --- 整形（ここが超重要） ---
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

        return [
            'name'       => $name,
            'workTimes'  => $workTimes,
            'breakTimes' => $breakTimes,
        ];
    }
}
