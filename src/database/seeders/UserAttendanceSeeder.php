<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserAttendanceSeeder extends Seeder
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

                    $note = null;
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

                    // ===== 修正処理 =====
                    $isCorrection = !$isForgot && rand(1, 100) <= 20;
                    $correctedBy = null;
                    $correctedAt = null;
                    if ($isCorrection) {
                        if (rand(1, 100) <= 90) {
                            $correctedBy = $user->id;
                        } else {
                            $correctedBy = $admins->random()->id;
                        }

                        $notes = [
                            '打刻漏れのため修正',
                            'システム不具合のため修正',
                            '体調不良により早退',
                            '電車遅延により遅刻',
                            'リモートワークに変更'
                        ];

                        $note = $notes[array_rand($notes)];

                    }

                    // ===== 承認処理 =====
                    $approved = $isCorrection && rand(1, 100) <= 60;
                    $admin = null;
                    if ($approved) {
                        if ($user->role == Role::ADMIN) {
                            $admin = $admins->where('id', '!=', $user->id)->first();
                        } else {
                            $admin = $admins->random();
                        }
                    }
                    $approvedAt = null;
                    if ($approved) {
                        // 修正して承認済み
                        $status = AttendanceStatus::APPROVED;
                        if ($i === 0) {
                            // 今月の場合の承認は本日
                            $approvedAt = Carbon::now();
                        } else {
                            $approvedAt = $currentDate->copy()->addDays(rand(2, 4));
                        }
                        $updatedAt = $approvedAt;
                        $correctedAt = $clockOut->copy()->addMinutes(30);
                    } elseif (!$approved && $isCorrection) {
                        // 修正はしたけど未承認
                        $status = AttendanceStatus::PENDING;
                        $updatedAt = $clockOut->copy()->addMinutes(30);
                        $correctedAt = $updatedAt;
                    } elseif ($isForgot) {
                        $status = AttendanceStatus::DRAFT;
                        $updatedAt = $clockIn;
                    } else {
                        // 修正なしの入力完了
                        $status = AttendanceStatus::COMPLETED;
                        $updatedAt =  $clockOut;
                    }

                    Attendance::create([
                        'user_id' => $user->id,
                        'work_date' => $currentDate->toDateString(),
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'created_by' => $user->id,
                        'corrected_by' => $correctedBy,
                        'corrected_at' => $correctedAt,
                        'approved_by' => $admin?->id,
                        'approved_at' => $approvedAt,
                        'status' => $status,
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
            $rand <= 15 => 'partTime', // 休憩なし
            $rand <= 75 => 'normal',   // 通常
            $rand <= 85 => 'late',     // 遅刻
            $rand <= 95 => 'early',    // 早退
            default => 'absent',       // 欠勤
        };
    }
}
