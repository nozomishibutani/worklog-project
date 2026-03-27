<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;

class BreakTimeSeeder extends Seeder
{
    public function run(): void
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {

            $clockIn = $attendance->clock_in;
            $clockOut = $attendance->clock_out;

            // 打刻漏れスキップ
            if (!$clockIn || !$clockOut) {
                continue;
            }

            $workMinutes = $clockIn->diffInMinutes($clockOut);

            // 4時間未満は休憩なし
            if ($workMinutes < 240) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | 休憩回数
            |--------------------------------------------------------------------------
             */
            // 4〜6時間 → 0〜1回
            // 6〜8時間 → 0〜2回
            // 8時間以上 → 1〜3回
            if ($workMinutes < 360) {
                $breakCount = rand(0, 1);
            } elseif ($workMinutes < 480) {
                $breakCount = rand(0, 2);
            } else {
                $breakCount = rand(1, 3);
            }

            if ($breakCount === 0) {
                continue;
            }

            $breaks = [];

            /*
            |--------------------------------------------------------------------------
            | 休憩生成
            |--------------------------------------------------------------------------
             */
            for ($i = 0; $i < $breakCount; $i++) {

                $length = $i === 0 ? 60 : (rand(0, 1) ? 15 : 30);

                $attempt = 0;

                // 既に休憩済みの場合は、被らずに休憩とれそうなところを10回まで探す
                while ($attempt < 10) {

                    $start = $clockIn->copy()->addMinutes(rand(0, $workMinutes - $length));
                    $end   = $start->copy()->addMinutes($length);

                    // 重複チェック
                    $overlap = false;
                    foreach ($breaks as $break) {
                        if ($start < $break['end'] && $end > $break['start']) {
                            $overlap = true;
                            break;
                        }
                    }

                    if (!$overlap) {
                        $breaks[] = [
                            'start' => $start,
                            'end'   => $end,
                        ];
                        break;
                    }
                    $attempt++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 保存
            |--------------------------------------------------------------------------
             */
            foreach ($breaks as $break) {

                // ==== 修正 ====
                //$correctedBy = null;
                $updatedAt = $break['end'];
                if ($attendance->corrected_by) {
                    //if (rand(1, 100) > 50) {
                    // 休憩を修正した場合
                    $updatedAt = $attendance->updated_at;
                    //$correctedBy = $attendance->corrected_by;
                    //}
                }

                BreakTime::create([
                    //'user_id'        => $attendance->user_id,
                    'attendance_id'  => $attendance->id,
                    'clock_in'       => $break['start'],
                    'clock_out'      => $break['end'],
                    //'created_by'     => $attendance->user_id,
                    //'corrected_by'   => $correctedBy,
                    'created_at'     => $break['start'],
                    'updated_at'     => $updatedAt,
                ]);
            }
        }
    }
}
