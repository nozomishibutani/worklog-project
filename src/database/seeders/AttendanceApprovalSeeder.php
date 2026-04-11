<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Attendance;
use App\Enums\ApprovalStatus;
use App\Enums\Role;
use App\Models\BreakTime;
use Illuminate\Database\Seeder;
use App\Models\AttendanceApproval;
use App\Models\AttendanceChange;
use App\Models\BreakTimeApproval;
use App\Models\BreakTimeChange;
use Carbon\Carbon;

class AttendanceApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $attendanceChanges = AttendanceChange::all();
        $admins = User::where('role', 'admin')->get();

        foreach ($attendanceChanges as $attendanceChange) {

            $clockIn = $attendanceChange->clock_in;
            $clockOut = $attendanceChange->clock_out;

            // ===== 承認処理 =====
            $approved = rand(1, 100) <= 50;

            if (!$approved) {
                continue;
            }

            if ($attendanceChange->user->role == Role::ADMIN) {
                $admin = $admins->where('id', '!=', $attendanceChange->user->id)->first();
            } else {
                $admin = $admins->random();
            }

            $approvedAt = $clockOut->copy()->addDays(rand(2, 4));
            if ($approvedAt->isFuture()) {
                continue;
            }

            $attendanceApproval = AttendanceApproval::create([
                'attendance_change_id' => $attendanceChange->id,
                'user_id' => $attendanceChange->user_id,
                'work_date' => $attendanceChange->work_date,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'approved_by' => $admin->id,
                'approved_at' => $approvedAt,
                'note' => $attendanceChange->note,
            ]);

            $breakTimes = BreakTimeChange::where('attendance_change_id', $attendanceChange->id)->get();
            if (!empty($breakTimes)) {
                foreach ($breakTimes as $breakTime) {
                    BreakTimeApproval::create([
                    'attendance_approval_id' => $attendanceApproval->id,
                    'clock_in' => $breakTime['clock_in'],
                    'clock_out' => $breakTime['clock_out'],
                    ]);
                }
            }
        }
    }
}
