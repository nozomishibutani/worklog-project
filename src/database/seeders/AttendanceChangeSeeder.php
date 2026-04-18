<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Database\Seeder;
use App\Models\AttendanceChange;
use App\Models\BreakTimeChange;


class AttendanceChangeSeeder extends Seeder
{
    public function run(): void
    {
        $attendances = Attendance::all();
        $admins = User::where('role', 'admin')->get();

        foreach ($attendances as $attendance) {

            $clockIn = $attendance->clock_in;
            $clockOut = $attendance->clock_out;

            // 打刻漏れ・本日の勤怠はスキップ
            if (!$clockIn || !$clockOut || $clockIn->isToday()) {
                continue;
            }

            // ===== 修正処理 =====
            $isCorrection =  rand(1, 100) <= 2;

            if (!$isCorrection) {
                continue;
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
            if ($key == 0  || $key == 1 || $key == 4) {
                $clockIn = $clockIn->copy()->setTime(rand(5, 8), rand(0, 5) * 10);
                $clockOut = $clockOut->copy()->setTime(rand(11, 20), rand(0, 5) * 10);
            } elseif ($key ==  2) {
                $clockOut = $clockOut->copy()->subMinute(rand(0, 5) * 10);
            } elseif ($key == 3) {
                $clockIn = $clockIn->copy()->addMinute(rand(0, 5) * 10);
            }
            $note = $notes[$key];

            // 修正履歴を作成
            $attendanceChange = AttendanceChange::create([
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'work_date' => $attendance->work_date,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'applied_at' => $clockOut->copy()->addHour(1),
                'applied_by' => $appliedBy,
                'note' => $note,
            ]);

            $breakTimes = BreakTime::where('attendance_id', $attendance->id)->get();
            if (!empty($breakTimes)) {
                foreach ($breakTimes as $breakTime) {
                    BreakTimeChange::create([
                    'attendance_change_id' => $attendanceChange->id,
                    'clock_in' => $breakTime['clock_in'],
                    'clock_out' => $breakTime['clock_out'],
                    ]);
                }
            }
        }
    }
}
