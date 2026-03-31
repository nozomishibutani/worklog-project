<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $admins = User::where('role', 'admin')->get();
        $firstAdmin = $admins->first();
        $member = $users->concat([$firstAdmin]);

        $base = Carbon::now()->startOfMonth();

        for ($i = 3; $i >= 0; $i--) {

            $month = $base->copy()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            for ($date = $start->copy(); $date <= $end ; $date->addDay()) {

                $currentDate = $date->copy()->startOfDay();

                foreach ($member as $user) {

                    $isToday = $currentDate->isToday();

                    if ($currentDate->isFuture()) {
                        continue;
                    }

                    if ($currentDate->isWeekend()) {
                        if (rand(1, 100) <= 80) {
                            continue;
                        }
                    }

                    // 勤務タイプ
                    $type = $this->randomWorkType();

                    // 欠勤
                    if ($type === 'absent') {
                        continue;
                    }

                    // ===== 勤務時間生成 =====
                    if ($isToday) {
                        $clockIn = Carbon::now()->subHours(rand(3, 8))
                                                            ->minute(rand(0, 3) * 15)
                                                            ->second(0);
                        $clockOut = Carbon::now()->subHours(1)
                                                            ->minute(rand(0, 3) * 15)
                                                            ->second(0);
                    } elseif ($type === 'partTime') {
                        $clockIn = $currentDate->copy()->setTime(14, rand(0, 3) * 15);
                        $clockOut = $currentDate->copy()->setTime(16, rand(0, 3) * 15);
                    } elseif ($type === 'normal') {
                        $clockIn = $currentDate->copy()->setTime(rand(8, 10), rand(0, 3) * 15);
                        $clockOut = $currentDate->copy()->setTime(rand(17, 19), rand(0, 3) * 15);
                    } elseif ($type === 'late') {
                        $clockIn = $currentDate->copy()->setTime(rand(10, 11), rand(0, 3) * 15);
                        $clockOut = $currentDate->copy()->setTime(rand(19, 20), rand(0, 3) * 15);
                    } elseif ($type === 'early') {
                        $clockIn = $currentDate->copy()->setTime(rand(6, 8), rand(0, 3) * 15);
                        $clockOut = $currentDate->copy()->setTime(rand(15, 17), rand(0, 3) * 15);
                    }

                    // ===== 打刻忘れ =====
                    $isForgot = rand(1, 100) <= 10;
                    if ($isForgot) {
                        $clockOut = null;
                    }

                    Attendance::create([
                        'user_id' => $user->id,
                        'work_date' => $currentDate->toDateString(),
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'created_by' => $user->id,
                        'created_at' => $clockIn,
                        'updated_at' => $clockOut,
                    ]);
                }
            }
        }
    }

    private function randomWorkType(): string
    {
        $rand = rand(1, 100);

        return match (true) {
            $rand <= 15 => 'partTime', // 休憩なし
            $rand <= 75 => 'normal',   // 通常
            $rand <= 85 => 'late',     // 遅刻
            $rand <= 95 => 'early',    // 早退
            default => 'absent',       // 欠勤
        };
    }
}
