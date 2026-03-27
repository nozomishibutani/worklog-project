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

        for ($i = 0; $i < 3; $i++) {

            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            for ($date = $end->copy(); $date->greaterThanOrEqualTo($start); $date->subDay()) {

                $date = $date->copy()->startOfDay();

                foreach ($member as $user) {

                    $note = null;
                    $isToday = $date->isToday();

                    if ($date->isFuture()) {
                        continue;
                    }

                    if ($date->isWeekend()) {
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
                        $clockIn = $date->copy()->setTime(14, rand(0, 3) * 15);
                        $clockOut = $date->copy()->setTime(16, rand(0, 3) * 15);
                    } elseif ($type === 'normal') {
                        $clockIn = $date->copy()->setTime(rand(8, 10), rand(0, 3) * 15);
                        $clockOut = $date->copy()->setTime(rand(17, 19), rand(0, 3) * 15);
                    } elseif ($type === 'late') {
                        $clockIn = $date->copy()->setTime(rand(10, 11), rand(0, 3) * 15);
                        $clockOut = $date->copy()->setTime(rand(19, 20), rand(0, 3) * 15);
                    } elseif ($type === 'early') {
                        $clockIn = $date->copy()->setTime(rand(6, 8), rand(0, 3) * 15);
                        $clockOut = $date->copy()->setTime(rand(15, 17), rand(0, 3) * 15);
                    } elseif ($type === 'night') {
                        $clockIn = $date->copy()->setTime(rand(20, 22), rand(0, 3) * 15);
                        $clockOut = $date->copy()->addDay()->setTime(rand(5, 7), rand(0, 3) * 15);
                    }

                    // ===== 打刻忘れ =====
                    $isForgot = rand(1, 100) <= 10;
                    if ($isForgot) {
                        $clockOut = null;
                    }

                    // ===== 重複 =====
                    if ($date->isYesterday()) {
                        $today = Attendance::where('user_id', $user->id)
                                                    ->where('work_date', Carbon::today())
                                                    ->first();

                        if ($today && !$isForgot) {
                            $todayClockIn = Carbon::parse($today->clock_in);
                            if ($todayClockIn->lessThan($clockOut)) {
                                // 翌日の勤務と被るなら作成しない
                                continue;
                            }
                        }
                    }

                    // ===== 修正処理 =====
                    $isCorrection = !$isForgot && rand(1, 100) <= 20;
                    $correctedBy = null;
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
                    $approved = $isCorrection && rand(1, 100) <= 70;
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
                            $approvedAt = $date->copy()->addDays(rand(2, 4));
                        }
                        $updatedAt = $approvedAt;
                    } elseif (!$approved && $isCorrection) {
                        // 修正はしたけど未承認
                        $status = AttendanceStatus::PENDING;
                        $updatedAt = $clockOut->copy()->addMinutes(10);
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
                        'work_date' => $date->toDateString(),
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'created_by' => $user->id,
                        'corrected_by' => $correctedBy,
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
            $rand <= 10 => 'partTime', // 休憩なし
            $rand <= 70 => 'normal',   // 通常
            $rand <= 85 => 'late',     // 遅刻
            $rand <= 90 => 'early',    // 早退
            $rand <= 95 => 'night',    // 夜勤
            default => 'absent',       // 欠勤
        };
    }
}
