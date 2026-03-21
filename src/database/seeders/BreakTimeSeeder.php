<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 例: 過去3か月の管理者勤怠を取得
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {

            $clockIn = $attendance->clock_in;
            $clockOut = $attendance->clock_out;

            $workMinutes = $clockOut->diffInMinutes($clockIn);
            $totalBreak = 0;

            // 総勤務時間が4時間以上なら休憩生成
            if ($workMinutes >= 240) {
                $maxBreaks = floor($workMinutes / 60);
                $breakCount = rand(1, $maxBreaks);

                for ($b = 0; $b < $breakCount; $b++) {
                    // 1回目は1時間、それ以降は15分か30分
                    $breakLength = $totalBreak < 60 ? 60 : (rand(0,1) ? 15 : 30);

                    // 勤務時間を超えないよう調整
                    if ($totalBreak + $breakLength > $workMinutes) {
                        $breakLength = $workMinutes - $totalBreak;
                        if ($breakLength <= 0) break;
                    }

                    $maxStart = $workMinutes - $breakLength;
                    $breakStart = $clockIn->copy()->addMinutes(rand(0, $maxStart));
                    $breakEnd = $breakStart->copy()->addMinutes($breakLength);

                    BreakTime::create([
                        'user_id' => $attendance->user_id,
                        'attendance_id' => $attendance->id,
                        'updated_by' => $attendance->updated_by,
                        'start_time' => $breakStart,
                        'end_time' => $breakEnd,
                    ]);

                    $totalBreak += $breakLength;
                }
            }
        }
    }
}
