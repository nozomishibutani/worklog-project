<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use Carbon\Carbon;
use App\Models\User;

class AttendanceService
{
    protected AttendanceRepository $attendanceRepository;

    public function __construct(AttendanceRepository $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * 特定日の全ユーザーの勤怠を取得
     */
    public function getAllUserDailyAttendances($date): array
    {
        $attendances = $this->attendanceRepository->getAllUserDailyAttendances($date);

        $workTimes = [];
        $breakTimes = [];
        $base = [];

        $invalidWorkUsers = [];  // 労働時間NG
        $invalidBreakUsers = []; // 休憩NG

        foreach ($attendances as $attendance) {
            $userId = $attendance->user_id;

            $clockIn  = strtotime($attendance->clock_in);
            if (!$clockIn) {
                continue;
            }

            $clockOut = $attendance->clock_out ? strtotime($attendance->clock_out) : null;

            // --- base ---
            if (!isset($base[$userId])) {
                $base[$userId] = [
                    'name' => $attendance->user->name,
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
     * 特定日のユーザーの勤怠詳細を取得
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

        // ---  休憩時間 ---
        $index = 1;
        foreach ($attendances->breakTimes as $breakTime) {
            $clockIn  = $breakTime->clock_in ? Carbon::parse($breakTime->clock_in) : null;
            $clockOut = $breakTime->clock_out ? Carbon::parse($breakTime->clock_out) : null;
            $breakTimes[$index] = [
                'id' => $breakTime->id,
                'clock_in' => $clockIn ? $clockIn->format('H:i') : null,
                'clock_out' => $clockOut ? $clockOut->format('H:i') : null,
            ];
            $index++;
        }
        $workTimes = array_merge($workTimes, $tmp);

        // --- 整形 ---
        ksort($breakTimes);
        return [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ];
    }


    /**
     * ユーザーの月次勤怠を取得
     */
    public function getUserMonthlyAttendances($userId, $date): array
    {
        $start = $date->copy()->startOfMonth();
        $end   = $date->copy()->endOfMonth();

        $attendances = $this->attendanceRepository->getUserMonthlyAttendances($userId, $start, $end);

        $workTimes = [];
        $breakTimes = [];
        $base = [];

        $invalidWorkUsers = [];  // 労働時間NG
        $invalidBreakUsers = []; // 休憩NG

        $user = User::find($userId);
        $name = $user->name;

        foreach ($attendances as $attendance) {
            $workDate = Carbon::parse($attendance->work_date)->format('Ymd');

            $clockIn  = strtotime($attendance->clock_in);
            if (!$clockIn) {
                continue;
            }

            $clockOut = $attendance->clock_out ? strtotime($attendance->clock_out) : null;

            // --- base ---
            $base = [
                'clock_in' => date('H:i', $clockIn),
                'clock_out' => $clockOut ? date('H:i', $clockOut) : null,
            ];

            // --- 休憩 ---
            $breakDiff = 0;
            $hasBreak = false;

            foreach ($attendance->breakTimes as $breakTime) {
                $hasBreak = true;

                // 戻りなし → 未確定
                if (!$breakTime->clock_out) {
                    $invalidBreakUsers[$workDate] = true;
                    break;
                }

                $breakIn  = strtotime($breakTime->clock_in);
                $breakOut = strtotime($breakTime->clock_out);
                $breakDiff += $breakOut - $breakIn;
            }

            // 休憩が成立している場合のみ加算
            if (!isset($invalidBreakUsers[$workDate]) && $hasBreak) {
                $breakTimes[$workDate] = ($breakTimes[$workDate] ?? 0) + $breakDiff;
            }

            // --- 労働時間 ---
            if (!$clockOut) {
                // 退勤なし → 未確定
                $invalidWorkUsers[$workDate] = true;
                continue;
            }

            // 休憩が壊れてたら労働時間も未確定
            if (isset($invalidBreakUsers[$workDate])) {
                $invalidWorkUsers[$workDate] = true;
                continue;
            }

            $workDiff = ($clockOut - $clockIn) - $breakDiff;
            $workTimes[$workDate] = ($workTimes[$workDate] ?? 0) + $workDiff;

            // 退勤済み & 休憩なし → 0確定
            if (!$hasBreak) {
                $breakTimes[$workDate] = 0;
            }

            // --- 整形 ---
            // 労働時間
            $workTimes[$workDate] = array_merge(
                $base,
                $this->formatTime(
                    isset($invalidWorkUsers[$workDate]) ? null : ($workTimes[$workDate] ?? null),
                )
            );

            if (isset($invalidBreakUsers[$workDate])) {
                // 戻り忘れ → 未確定
                $breakTimes[$workDate] = $this->formatTime(null);

            } elseif (isset($breakTimes[$workDate])) {
                // 休憩成立 → 表示
                $breakTimes[$workDate] = $this->formatTime($breakTimes[$workDate]);

            } else {
                // 休憩なし
                if ($base['clock_out']) {
                    // 退勤済み → 0確定
                    $breakTimes[$workDate] = $this->formatTime(0);
                } else {
                    // 退勤なし → 未確定
                    $breakTimes[$workDate] = $this->formatTime(null);
                }
            }
        }

        // 勤怠がない日は空レコード作成
        $tmp = [];
        for ($i = $start->copy(); $i->lte($end); $i->addDay()) {

            $date = $i->format('Ymd');
            $formatDate = $this->formatDate($i->copy());

            // その日の勤怠データ
            $work = $workTimes[$date] ?? [];
            if (empty($work)) {
                $work = $this->formatTime(null);
            }

            // 空データ + 勤怠データ + 表示日付 をマージ
            $tmp[$date] = array_merge(
                [
                    'clock_in'      => null,
                    'clock_out'     => null,
                ],
                $work,
                [
                    'display_date' => $formatDate,
                ]
            );
        }

        ksort($tmp);
        ksort($breakTimes);

        return [
            'name' => $name,
            'workTimes' => $tmp,
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
    private function formatTime(?int $seconds, bool $padHour = false): array
    {
        if ($seconds === null) {
            return [
                'hours'         => null,
                'minutes'       => null,
                'display_total' => null,
            ];
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        // $padHour = true → 2桁ゼロ埋め
        // $padHour = false → 1桁可
        $hourStr = $padHour ? str_pad($hours, 2, '0', STR_PAD_LEFT) : (string)$hours;
        $minuteStr = str_pad($minutes, 2, '0', STR_PAD_LEFT);

        return [
            'hours'         => $hours,
            'minutes'       => $minuteStr,
            'display_total' => $hourStr . ':' . $minuteStr,
        ];
    }

    /**
     * 日付を「月日(曜日)」形式にフォーマットする
     *
     * @param string $date フォーマットしたい日付
     * @return string フォーマット済みの日付文字列（例: 03月06日（木））
     */
    private function formatDate($date): string
    {
        $date = Carbon::parse($date);
        $weekdays = ['日','月','火','水','木','金','土'];
        return $date->format('m月d日') . '（' . $weekdays[$date->dayOfWeek] . '）';
    }
}
