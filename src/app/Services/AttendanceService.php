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
                $this->formatAttendanceRow(
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
                $this->formatAttendanceRow(
                    $base,
                    $workMinutes[$key] ?? null,
                    $breakMinutes[$key] ?? null,
                    isset($invalidWorkUsers[$key]),
                    isset($invalidBreakUsers[$key])
                );
        }

        // 空日付埋める
        $workTimes = $this->createMonthlyEmptyRecords($start, $end, $workTimes);

        ksort($workTimes);
        ksort($breakTimes);

        return [
            'name'       => $name,
            'workTimes'  => $workTimes,
            'breakTimes' => $breakTimes,
        ];
    }


    /**
     * 勤怠1件分の表示用データを整形する
     *
     * @param array $base 基本情報（例: ['name', 'clock_in', 'clock_out']）
     * @param int|null $workMinutes 労働時間（分）。nullの場合は未確定として扱う
     * @param int|null $breakMinutes 休憩時間（分）。nullの場合は未確定または未取得
     * @param bool $invalidWork 労働時間が無効かどうか（退勤打刻漏れなど）
     * @param bool $invalidBreak 休憩時間が無効かどうか（休憩戻り打刻漏れなど）
     *
     * @return array{
     *     0: array{
     *         clock_in: string|null,
     *         clock_out: string|null,
     *         hours: int|null,
     *         minutes: string|null,
     *         display_total: string|null
     *     },
     *     1: array{
     *         hours: int|null,
     *         minutes: string|null,
     *         display_total: string|null
     *     }
     * }
     */
    private function formatAttendanceRow(
        array $base,
        ?int $workMinutes,
        ?int $breakMinutes,
        bool $invalidWork,
        bool $invalidBreak
    ): array {

        // --- 労働時間 ---
        $work = array_merge(
            $base,
            $this->formatTime(
                $invalidWork ? null : $workMinutes
            )
        );

        // --- 休憩 ---
        if ($invalidBreak) {
            $break = $this->formatTime(null);

        } elseif ($breakMinutes !== null) {
            $break = $this->formatTime($breakMinutes);

        } else {
            $break = $base['clock_out']
                ? $this->formatTime(0)
                : $this->formatTime(null);
        }

        return [$work, $break];
    }

    /**
     * 分数を「時間・分」にフォーマットする
     *
     * @param int|null $minutes 分数（nullの場合は空データとして扱う）
     * @param bool $padHour 2桁ゼロ埋めするか
     * @return array{
     *     minutes: int|null,
     *     hours: string|null,
     *     display_total: string|null
     * }
     */
    private function formatTime(?int $minutes, bool $padHour = false): array
    {
        if ($minutes === null) {
            return [
                'hours'         => null,
                'minutes'       => null,
                'display_total' => null,
            ];
        }

        $hours = floor($minutes / 60);
        $mins  = $minutes % 60;

        // $padHour = true → 2桁ゼロ埋め
        $hourStr   = $padHour ? str_pad($hours, 2, '0', STR_PAD_LEFT) : (string)$hours;
        $minuteStr = str_pad($mins, 2, '0', STR_PAD_LEFT);

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


    /**
     * 月間の空レコード作成をする
     *
     * @param Carbon $start 取得する月の初日
     * @param Carbon $end 取得する月の最終日
     * @param array $workTimes 勤務日
     * @return array 月次勤怠
     */
    private function createMonthlyEmptyRecords(Carbon $start, Carbon $end, array $workTimes): array
    {
        $result = [];

        for ($i = $start->copy(); $i->lte($end); $i->addDay()) {
            $key = $i->format('Ymd');
            $formatDate = $this->formatDate($i);

            $work = $workTimes[$key] ?? $this->formatTime(null);

            $result[$key] = array_merge(
                ['clock_in' => null, 'clock_out' => null],
                $work,
                ['display_date' => $formatDate]
            );
        }

        ksort($result);
        return $result;
    }
}
