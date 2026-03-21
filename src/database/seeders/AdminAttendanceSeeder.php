<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AdminAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $admins = User::where('role', 'admin')->get();
        $admin = $admins[0];      // 1人目の管理者
        $otherAdmin = $admins[1]; // 2人目の管理者

            for ($i = 0; $i < 3; $i++) {

                $month = Carbon::now()->subMonths($i);
                $start = $month->copy()->startOfMonth();
                $end = $month->copy()->endOfMonth();
                $note = null;

                for ($date = $start->copy(); $date <= $end; $date->addDay()) {

                    if ($date->isWeekend()) continue;

                    if ($date->isFuture()) continue;

                    // 勤務タイプ
                    $type = $this->randomWorkType();

                    // 欠勤
                    if ($type === 'absent') continue;

                    // 打刻忘れ（レア）
                    $isForgot = rand(1, 100) <= 3;

                    // 今日のレコードは承認させない
                    $isToday = $date->isToday();

                    // ===== 勤務時間生成 =====
                    if ($type === 'normal') {
                        $clockIn = $date->copy()->setTime(rand(8, 10), rand(0, 59));
                        $clockOut = $date->copy()->setTime(rand(17, 19), rand(0, 59));
                    }

                    elseif ($type === 'late') {
                        $clockIn = $date->copy()->setTime(rand(10, 11), rand(0, 59));
                        $clockOut = $date->copy()->setTime(rand(18, 20), rand(0, 59));
                        $note = '遅刻';
                    }

                    elseif ($type === 'early') {
                        $clockIn = $date->copy()->setTime(rand(8, 9), rand(0, 59));
                        $clockOut = $date->copy()->setTime(rand(15, 17), rand(0, 59));
                        $note = '早退';
                    }

                    elseif ($type === 'night') {
                        $clockIn = $date->copy()->setTime(rand(20, 22), rand(0, 59));
                        $clockOut = $date->copy()->addDay()->setTime(rand(5, 7), rand(0, 59));
                    }

                    // ===== 打刻忘れ =====
                    if ($isForgot) {
                        $clockOut = null;
                        // 本日扱いにして承認させない
                        $isToday = true;
                    }

                    // ===== 修正処理 =====
                    $isEdited = !$isForgot && rand(1, 100) <= 20;
                    $updatedBy = null;
                    if ($isEdited) {
                        if (rand(1, 100) <= 80) {
                            $updatedBy = $admin->id;
                        } else {
                            $updatedBy = $otherAdmin->id;
                            $note = ($note ?? '') . '【管理者修正】';
                        }
                    }

                    // ===== 承認処理 =====
                    $approved = !$isForgot && !$isToday && rand(1, 100) <= 70;
                    if ($approved) {
                        if ($i === 0) {
                            // 今月の場合の承認は本日
                            $approvedAt = Carbon::now();
                        } else {
                            $approvedAt = $date->copy()->addDays(rand(2, 3));
                        }
                    } else {
                        $approvedAt = null;
                    }

                    // ===== リクエスト日 =====
                    if ($isToday) {
                        $requestedAt = null;
                    } elseif ($type === 'night') {
                        $requestedAt = $date->copy()->addDay(2);
                    } else {
                        $requestedAt = $date->copy()->addDay();
                    }

                    Attendance::create([
                        'user_id' => $admin->id,
                        'work_date' => $date->toDateString(),
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'created_by' => $admin->id,
                        'updated_by' => $updatedBy,
                        'approved_by' => $approved ? $otherAdmin->id : null,
                        'approved_at' => $approvedAt,
                        'status' => $isToday ? AttendanceStatus::DRAFT
                            : ($approved ? AttendanceStatus::APPROVED : AttendanceStatus::PENDING),
                        'requested_at' => $requestedAt,
                        'note' => $note,
                    ]);
                }
            }
    }

    private function randomWorkType(): string
    {
        $rand = rand(1, 100);

        return match (true) {
            $rand <= 70 => 'normal',   // 通常
            $rand <= 80 => 'late',     // 遅刻
            $rand <= 90 => 'early',    // 早退
            $rand <= 98 => 'night',    // 夜勤
            default => 'absent',       // 欠勤
        };
    }
}