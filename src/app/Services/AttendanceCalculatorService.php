<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use App\Services\AttendanceResolverService;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceApproval;
use App\Models\AttendanceChange;
use App\Models\AttendanceHistory;
use Illuminate\Support\Facades\Auth;

use function Symfony\Component\VarDumper\Dumper\esc;

class AttendanceCalculatorService
{
    protected AttendanceRepository $attendanceRepository;
    protected AttendanceFormatterService $attendanceFormatterService;
    protected AttendanceResolverService $attendanceResolverService;

    public function __construct(
        AttendanceFormatterService $attendanceFormatterService,
        AttendanceRepository $attendanceRepository,
        AttendanceResolverService $attendanceResolverService,
    ) {
        $this->attendanceFormatterService = $attendanceFormatterService;
        $this->attendanceRepository = $attendanceRepository;
        $this->attendanceResolverService =  $attendanceResolverService;
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
    public function getUserDailyAttendance(Attendance|AttendanceChange|AttendanceApproval $attendance): array
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
                'name' => $attendance->user->name,
                'clock_in' => $clockIn ? $clockIn->format('H:i') : null,
                'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
                ];
        $workTimes = array_merge($workTimes, $tmp);

        // ---  休憩時間 ---
        $arr = $attendance->breakTimes ?? $attendance->breakTimeHistories;
        if (!empty($arr)) {
            foreach ($arr as $breakTime) {
                $clockIn  = $breakTime->clock_in ? Carbon::parse($breakTime->clock_in) : null;
                $clockOut = $breakTime->clock_out ? Carbon::parse($breakTime->clock_out) : null;
                $breakTimes[] = [
                    'id' => $breakTime->id,
                    'clock_in' => $clockIn ? $clockIn->format('H:i') : null,
                    'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
                ];
            }
        }
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
        $workTimes = [];
        $breakTimes = [];

        $invalidWorkUsers = [];
        $invalidBreakUsers = [];

        $workMinutes = [];
        $breakMinutes = [];

        $baseList = [];

        $start = $startOfMonth;
        $end   = $startOfMonth->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $userId)
                            ->whereBetween('work_date', [$start, $end])
                            ->get('id');

        foreach ($attendances as $attendance) {
            [
                'currentAttendanceStatus' => $currentAttendanceStatus,
                'currentAttendance' => $currentAttendance,
            ]
            = $this->attendanceResolverService->getCurrentAttendance($attendance->id, null);

            $workDate = Carbon::parse($currentAttendance->work_date);
            $key = $workDate->format('Ymd');

            $clockIn  = $currentAttendance->clock_in ? Carbon::parse($currentAttendance->clock_in) : null;
            $clockOut = $currentAttendance->clock_out ? Carbon::parse($currentAttendance->clock_out) : null;

            // --- base ---
            $baseList[$key] = [
                'attendance_id' => $attendance->id,
                'clock_in'  => $clockIn ? $clockIn->format('H:i') : null,
                'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
            ];

            // --- 休憩 ---
            $breakDiff = 0;
            $hasBreak = false;

            foreach ($currentAttendance->breakTimes as $breakTime) {
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
}
