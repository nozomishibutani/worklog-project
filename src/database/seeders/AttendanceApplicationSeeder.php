<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Enums\ApprovalStatus;
use App\Enums\Role;
use App\Models\BreakTime;
use Illuminate\Database\Seeder;
use App\Models\AttendanceApplication;

class AttendanceApplicationSeeder extends Seeder
{
    public function run(): void
    {

        $attendances = Attendance::all();
        $admins = User::where('role', 'admin')->get();

        foreach ($attendances as $attendance) {

            $clockIn = $attendance->clock_in;
            $clockOut = $attendance->clock_out;
            $admin = null;
            $appliedAt = null;
            $approvedAt = null;
            $appliedBy = null;

            // 打刻漏れスキップ
            if (!$clockIn || !$clockOut) {
                continue;
            }

            // ===== 修正処理 =====
            $isCorrection =  rand(1, 100) <= 3;

            if ($isCorrection) {
                if (rand(1, 100) <= 80) {
                    // 自分で修正
                    $appliedBy = $attendance->user->id;
                } else {
                    // 管理者が修正
                    $$appliedBy = $admins->random()->id;
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
            $approved = $isCorrection && rand(1, 100) <= 50;
            if ($approved) {
                if ($attendance->user->role == Role::ADMIN) {
                    $admin = $admins->where('id', '!=', $attendance->user->id)->first();
                } else {
                    $admin = $admins->random();
                }
            }

            if (!$approved && !$isCorrection) {
                // 修正も承認もなし
                continue;
            }

            if ($approved) {
                // 修正して承認済み
                $approvedAt = $clockOut->copy()->addDays(rand(2, 4));
                $updatedAt = $approvedAt;

                if ($approvedAt->isFuture()) {
                    continue;
                }

            } elseif (!$approved && $isCorrection) {
                $updatedAt = $clockOut->copy()->addMinutes(30);
            }

            $appliedAt = $clockOut->copy()->addHour(1);

            AttendanceApplication::create([
                'attendance_id' => $attendance->id,
                'applied_by' => $appliedBy,
                'applied_at' => $appliedAt,
                'approved_by' => $admin?->id,
                'approved_at' => $approvedAt,
                'note' => $note,
                'created_at' => $clockIn,
                'updated_at' => $updatedAt,
            ]);

            // attendance breakTimes それぞれ更新する
            $targetAttendance = Attendance::find($attendance->id);
            $targetAttendance->updated_at = $updatedAt;
            $targetAttendance->save();
            BreakTime::where('attendance_id', $attendance->id)->update(['updated_at' => $updatedAt]);
        }
    }
}
