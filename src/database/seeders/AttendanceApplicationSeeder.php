<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Enums\ApprovalStatus;
use App\Enums\Role;
use App\Models\BreakTime;
use Illuminate\Database\Seeder;
use App\Models\AttendanceApplication;
use App\Models\AttendanceHistory;
use App\Models\BreakTimeHistory;
use Carbon\Carbon;

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
            // 本日の勤怠もスキップ
            if (!$clockIn || !$clockOut || $clockIn->isToday()) {
                continue;
            }

            // ===== 修正処理 =====
            $isCorrection =  rand(1, 100) <= 3;

            if ($isCorrection) {
                // 修正履歴を作成
                $attendanceHistory = AttendanceHistory::create([
                    'user_id' => $attendance->user_id,
                    'work_date' => $attendance->work_date,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'created_by' => $attendance->created_by,
                    'created_at' => $attendance->created_at,
                    'updated_at' => $attendance->updated_at,
                ]);

                $breakTimes = BreakTime::where('attendance_id', $attendance->id)->get();
                if (!empty($breakTimes)) {
                    foreach ($breakTimes as $breakTime) {
                        BreakTimeHistory::create([
                        'attendance_history_id' => $attendanceHistory->id,
                        'clock_in' => $breakTime['clock_in'],
                        'clock_out' => $breakTime['clock_out'],
                        'created_by' => $breakTime['created_by'],
                        'created_at' => $breakTime['created_at'],
                        'updated_at' => $breakTime['updated_at'],
                    ]);
                    }
                }


                if (rand(1, 100) <= 80) {
                    // 自分で修正
                    $appliedBy = $attendance->user_id;
                } else {
                    // 管理者が修正
                    $appliedBy = $admins->random()->id;
                }

                $notes = [
                    '打刻漏れのため修正',
                    'システム不具合のため修正',
                    '体調不良により早退',
                    '電車遅延により遅刻',
                    'リモートワークに変更'
                ];

                $key = array_rand($notes);
                // ===== 修正勤務時間生成 =====
                if ($key == 0  || $key == 1 || $key == 4) {
                    $clockIn = $clockIn->copy()->setTime(rand(5, 8), rand(0, 5) * 10);
                    $clockOut = $clockOut->copy()->setTime(rand(11, 20), rand(0, 5) * 10);
                } elseif ($key ==  2) {
                    $clockOut = $clockOut->copy()->subMinute(rand(0, 5) * 10);
                } elseif ($key == 3) {
                    $clockIn = $clockIn->copy()->addMinute(rand(0, 5) * 10);
                }
                $note = $notes[$key];
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

            $appliedAt = $clockOut->copy()->addHour(1);

            if ($approved) {
                // 修正して承認済み
                $approvedAt = $clockOut->copy()->addDays(rand(2, 4));
                $updatedAt = $approvedAt;

                if ($approvedAt->isFuture()) {
                    continue;
                }

            } elseif (!$approved && $isCorrection) {
                $updatedAt = $appliedAt;
            }

            AttendanceApplication::create([
                'attendance_id' => $attendance->id,
                'attendance_history_id' => $attendanceHistory->id,
                'applied_by' => $appliedBy,
                'applied_at' => $appliedAt,
                'approved_by' => $admin?->id,
                'approved_at' => $approvedAt,
                'note' => $note,
                'is_current' => true,
                'created_at' => $appliedAt,
                'updated_at' => $updatedAt,
            ]);

            // attendance breakTimes それぞれ更新する
            $targetAttendance = Attendance::find($attendance->id);
            $targetAttendance->clock_in = $clockIn;
            $targetAttendance->clock_out = $clockOut;
            $targetAttendance->updated_at = $appliedAt;
            $targetAttendance->save();
            BreakTime::where('attendance_id', $attendance->id)->update(['updated_at' => $appliedAt]);
        }
    }
}
