<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Database\Seeder;
use App\Models\AttendanceApplication;
use App\Models\AttendanceHistory;
use App\Models\BreakTimeHistory;
use Symfony\Component\Console\Application;

class AttendanceHistorySeeder extends Seeder
{
    public function run(): void
    {
        // 承認済みもレコードを取得
        $attendances = AttendanceApplication::with('attendance.user')->whereNotNull('approved_by')->whereNotNull('approved_at')->get();
        foreach ($attendances as $attendance) {
            $attendanceHistory =  AttendanceHistory::create([
            'user_id' => $attendance['attendance']['user_id'],
            'work_date' => $attendance['attendance']['work_date'],
            'clock_in' => $attendance['attendance']['clock_in'],
            'clock_out' => $attendance['attendance']['clock_out'],
            'created_by' => $attendance['attendance']['created_by'],
            'created_at' => $attendance['attendance']['created_at'],
            'updated_at' => $attendance['attendance']['updated_at'],
        ]);

            $breakTimes = BreakTime::where('attendance_id', $attendance->attendance_id)->get();
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

            //$attendanceApplication = AttendanceApplication::find($attendance->id);
            $attendance->attendance_history_id = $attendanceHistory->id;
            $attendance->save();
        }
    }
}
