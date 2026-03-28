<?php

namespace App\Services;

use Carbon\Carbon;

class AttendanceFormatterService
{
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
    public function formatAttendanceRow(
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
    public function formatTime(?int $minutes, bool $padHour = false): array
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
    public function formatDate($date): string
    {
        $date = Carbon::parse($date);
        $weekdays = ['日','月','火','水','木','金','土'];
        return $date->format('m月d日') . '（' . $weekdays[$date->dayOfWeek] . '）';
    }

    /**
     * 日付けをCarbon形式にフォーマットする
     *
     * @param array $date フォーマットしたい日付
     * @param string $time フォーマットしたい時刻
     * @return Carbon
     */
    public function formatCarbonDate(array $date, string|null $time = null): Carbon
    {
        if ($time == null) {
            $res = Carbon::create(
                $date['year'],
                $date['month'],
                $date['day'],
            );

            return $res;

        }
        [$hour, $minute] = explode(':', $time);
        $res = Carbon::create(
            $date['year'],
            $date['month'],
            $date['day'],
            $hour,
            $minute
        );

        return $res;
    }


    /**
     * 空レコード作成しデータを整形する
     *
     * @param Carbon $start 取得する月の初日
     * @param Carbon $end 取得する月の最終日
     * @param array $workTimes 勤務日
     * @return array 月次勤怠
     */
    public function createMonthlyEmptyRecords(Carbon $start, Carbon $end, array $workTimes): array
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
