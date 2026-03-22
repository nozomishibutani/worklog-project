<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $admins = User::where('role', 'admin')->get();

        for ($i = 0; $i < 3; $i++) {

            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            for ($date = $start->copy(); $date <= $end; $date->addDay()) {

                foreach ($users as $user) {

                    $note = null;

                    if ($date->isToday()) {
                        continue;
                    }

                    if ($date->isFuture()) {
                        continue;
                    }

                    if ($date->isWeekend()) {
                        if (rand(1, 100) <= 80) {
                            continue;
                        }
                        $note = ($note ?? '') . '休日出勤';
                    }

                    // 勤務タイプ
                    $type = $this->randomWorkType();

                    // 欠勤
                    if ($type === 'absent') {
                        continue;
                    }

                    // ===== 勤務時間生成 =====
                    if ($type === 'partTime') {
                        $clockIn = $date->copy()->setTime(9, rand(50, 59));
                        $clockOut = $date->copy()->setTime(13, rand(0, 10));
                    }

                    if ($type === 'normal') {
                        $clockIn = $date->copy()->setTime(rand(8, 10), rand(0, 59));
                        $clockOut = $date->copy()->setTime(rand(17, 19), rand(0, 59));
                    } elseif ($type === 'late') {
                        $clockIn = $date->copy()->setTime(rand(10, 11), rand(0, 59));
                        $clockOut = $date->copy()->setTime(rand(18, 20), rand(0, 59));
                        $note = '遅刻';
                    } elseif ($type === 'early') {
                        $clockIn = $date->copy()->setTime(rand(8, 9), rand(0, 59));
                        $clockOut = $date->copy()->setTime(rand(15, 17), rand(0, 59));
                        $note = '早退';
                    } elseif ($type === 'night') {
                        $clockIn = $date->copy()->setTime(rand(20, 22), rand(0, 59));
                        $clockOut = $date->copy()->addDay()->setTime(rand(5, 7), rand(0, 59));
                    }

                    // ===== 打刻忘れと承認申請忘れ =====
                    $isTarget = rand(1, 100) <= 20;
                    if ($isTarget) {
                        if (rand(1, 100) <= 40) {
                            $clockOut = null;
                        }
                        $note = null;
                    }

                    // ===== 承認処理 =====
                    $approved = !$isTarget && rand(1, 100) <= 70;
                    $admin = $approved ? $admins->random() : null;
                    $status = AttendanceStatus::PENDING;
                    $approvedAt = null;
                    if ($approved) {
                        $status = AttendanceStatus::APPROVED;
                        if ($i === 0) {
                            // 今月の場合の承認は本日
                            $approvedAt = Carbon::now();
                        } else {
                            $approvedAt = $date->copy()->addDays(rand(2, 4));
                        }
                    }

                    // ===== 修正処理 =====
                    $isEdited = !$isTarget && rand(1, 100) <= 20;
                    $updatedBy = null;
                    if ($isEdited) {
                        if (rand(1, 100) <= 80) {
                            $updatedBy = $user->id;
                        } else {
                            $updatedBy = $admins->random()->id;
                            $note = ($note ?? '') . '【管理者修正あり】';
                        }
                    }

                    // ===== リクエスト日 =====
                    if ($isTarget) {
                        $requestedAt = null;
                        $status = AttendanceStatus::DRAFT;
                    } elseif ($type === 'night') {
                        $requestedAt = $date->copy()->addDay(2);
                    } else {
                        $requestedAt = $date->copy()->addDay();
                    }

                    if ($status != AttendanceStatus::DRAFT && !$note) {
                        $note = '特になし';
                    }

                    // ==== 更新時間 ====
                    if ($updatedBy) {
                        $updatedAt = $requestedAt->copy()->subMinutes(5);
                    } elseif (!$clockOut) {
                        $updatedAt = $clockIn;
                    } else {
                        $updatedAt =  $clockOut;
                    }

                    Attendance::create([
                        'user_id' => $user->id,
                        'work_date' => $date->toDateString(),
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'created_by' => $user->id,
                        'updated_by' => $updatedBy,
                        'approved_by' => $admin?->id,
                        'approved_at' => $approvedAt,
                        'status' => $status,
                        'requested_at' => $requestedAt,
                        'note' => $note,
                        'created_at' => $clockIn,
                        'updated_at' => $updatedAt,
                    ]);
                }
            }
        }
    }

    private function randomWorkType(): string
    {
        $rand = rand(1, 100);

        return match (true) {
            $rand <= 10 => 'partTime', // 休憩なし
            $rand <= 70 => 'normal',   // 通常
            $rand <= 85 => 'late',     // 遅刻
            $rand <= 90 => 'early',    // 早退
            $rand <= 95 => 'night',    // 夜勤
            default => 'absent',       // 欠勤
        };
    }
}
