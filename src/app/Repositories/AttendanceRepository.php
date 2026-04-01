<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceApplication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceRepository
{
    /**
     * 特定日の全ユーザーの勤怠を取得
     */
    public function getAllUserDailyAttendances($date): Collection
    {
        return User::select('id', 'name')->with([
            'attendances' => function ($query) use ($date) {
                $query->whereDate('work_date', $date);
            },
            'attendances.breakTimes'
        ])->get();
    }

    /**
     * 特定日のユーザーの勤怠を取得
     */
    public function getUserDailyAttendance($attendanceId)
    {
        return Attendance::with(['user', 'breakTimes', 'attendanceApplication'])->find($attendanceId);

    }

    /**
     * ユーザーごとの月次勤怠を取得
     */
    public function getUserMonthlyAttendances($userId, $start, $end): Collection
    {
        return Attendance::with(['user', 'breakTimes'])
                            ->where('user_id', $userId)
                            ->whereBetween('work_date', [$start, $end])
                            ->get();
    }

    public function updateAttendance(array $attendance, array $formatCarbonDate, ?Attendance $targetAttendance): AttendanceApplication
    {
        return DB::transaction(function () use ($attendance, $formatCarbonDate, $targetAttendance) {

            // 勤怠日
            if (is_null($targetAttendance)) {
                // 新規登録
                $targetAttendance = Attendance::create([
                    'user_id'      => $attendance['user_id'],
                    'work_date'    => $formatCarbonDate['work_date'],
                    'created_by'   => Auth::id(),
                    'clock_in'     => $formatCarbonDate['work_in'],
                    'clock_out'    => $formatCarbonDate['work_out'],
                ]);

            } else {
                // 更新
                $targetAttendance->clock_in = $formatCarbonDate['work_in'];
                $targetAttendance->clock_out = $formatCarbonDate['work_out'];
                $targetAttendance->save();
            }

            // 休憩
            foreach ($attendance['break_in'] as $id => $breakIn) {
                if ($id !== 0 && is_null($attendance['break_in'][$id]) && is_null($attendance['break_out'][$id])) {
                    // 削除
                    BreakTime::find($id)->delete();
                } elseif (!is_null($attendance['break_in'][$id]) && !is_null($attendance['break_out'][$id])) {
                    // 何かしら入力があるとき
                    BreakTime::updateOrCreate(
                        ['id' => $id,  'attendance_id' => $targetAttendance->id],
                        [
                            'clock_in'  => $formatCarbonDate['break_in'][$id],
                            'clock_out' => $formatCarbonDate['break_out'][$id],
                        ]
                    );
                }
            }
            // 承認
            return AttendanceApplication::create([
                    'attendance_id' => $targetAttendance->id,
                    'applied_by'    => Auth::id(),
                    'applied_at'    => now(),
                    'approved_by'  => Auth::id(),
                    'approved_at'  => now(),
                    'note'         => $attendance['note'],
                    'status'       => AttendanceStatus::APPROVED,
                ]);
        });
    }
}
