<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Enums\Type;
use Carbon\Carbon;

class AttendanceService
{
    protected AttendanceRepository $attendanceRepository;

    public function __construct(AttendanceRepository $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * 勤怠の取得範囲
     */
    public function getByPeriod($date, $type = Type::TYPE_DAILY)
    {
        $date = Carbon::parse($date);
        if ($type === Type::TYPE_MONTHLY->value) {
            $start = $date->copy()->startOfMonth();
            $end   = $date->copy()->endOfMonth();
        } else {
            $start = $date->copy()->startOfDay();
            $end   = $date->copy()->endOfDay();
        }

        $attendances = $this->attendanceRepository->getAttendances($start, $end);
        return $this->getAttendances($attendances);
    }

    /**
     * 勤怠を取得して、必要な加工をする
     */
    public function getAttendances($attendances) : array
    {
        $workTimes = [];
        $breakTimes = [];
        $base = [];

        $invalidWorkUsers = [];  // 労働時間NG
        $invalidBreakUsers = []; // 休憩NG

        foreach ($attendances as $attendance) {
            $userId = $attendance->user_id;

            $clockIn  = strtotime($attendance->clock_in);
            if (!$clockIn) continue;

            $clockOut = $attendance->clock_out ? strtotime($attendance->clock_out) : null;

            // --- base ---
            if (!isset($base[$userId])) {
                $base[$userId] = [
                    'name' => $attendance->user->name,
                    'work_date' => $attendance->work_date,
                    'clock_in' => date('H:i', $clockIn),
                    'clock_out' => $clockOut ? date('H:i', $clockOut) : null,
                ];
            }

            // --- 休憩 ---
            $breakDiff = 0;
            $hasBreak = false;

            foreach ($attendance->breakTimes as $breakTime) {
                $hasBreak = true;

                // 戻りなし → 未確定
                if (!$breakTime->clock_out) {
                    $invalidBreakUsers[$userId] = true;
                    break;
                }

                $breakIn  = strtotime($breakTime->clock_in);
                $breakOut = strtotime($breakTime->clock_out);
                $breakDiff += $breakOut - $breakIn;
            }

            // 休憩が成立している場合のみ加算
            if (!isset($invalidBreakUsers[$userId]) && $hasBreak) {
                $breakTimes[$userId] = ($breakTimes[$userId] ?? 0) + $breakDiff;
            }

            // --- 労働時間 ---
            if (!$clockOut) {
                // 退勤なし → 未確定
                $invalidWorkUsers[$userId] = true;
                continue;
            }

            // 休憩が壊れてたら労働時間も未確定
            if (isset($invalidBreakUsers[$userId])) {
                $invalidWorkUsers[$userId] = true;
                continue;
            }

            $workDiff = ($clockOut - $clockIn) - $breakDiff;
            $workTimes[$userId] = ($workTimes[$userId] ?? 0) + $workDiff;

            // 退勤済み & 休憩なし → 0確定
            if (!$hasBreak) {
                $breakTimes[$userId] = 0;
            }
        }

        // --- 整形 ---
        foreach ($base as $userId => $baseData) {

            // 労働時間
            $workTimes[$userId] = array_merge(
                $baseData,
                $this->formatTime(
                    isset($invalidWorkUsers[$userId]) ? null : ($workTimes[$userId] ?? null)
                )
            );

            if (isset($invalidBreakUsers[$userId])) {
                // 戻り忘れ → 未確定
                $breakTimes[$userId] = $this->formatTime(null);

            } elseif (isset($breakTimes[$userId])) {
                // 休憩成立 → 表示
                $breakTimes[$userId] = $this->formatTime($breakTimes[$userId]);

            } else {
                // 休憩なし
                if ($baseData['clock_out']) {
                    // 退勤済み → 0確定
                    $breakTimes[$userId] = $this->formatTime(0);
                } else {
                    // 退勤なし → 未確定
                    $breakTimes[$userId] = $this->formatTime(null);
                }
            }
        }

        ksort($workTimes);
        ksort($breakTimes);

        return [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ];
    }

    /**
     * 秒数を「時間・分」にフォーマットする
     *
     * @param int|null $seconds 秒数（nullの場合は空データとして扱う）
     * @return array{
     *     seconds: int|null,
     *     hours: string|null,
     *     minutes: string|null
     * }
     */
    private function formatTime(?int $seconds): array
    {
        if ($seconds === null) {
            return [
                'seconds' => null,
                'hours'   => null,
                'minutes' => null,
                'display' => null,
            ];
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return [
                'seconds' => $seconds,
                'hours'   => $hours,
                'minutes' => str_pad($minutes, 2, '0', STR_PAD_LEFT),
                'display' => $hours. ' : ' .str_pad($minutes, 2, '0', STR_PAD_LEFT),
            ];
    }
}
