<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;
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
        // return Attendance::with(['user', 'breakTimes'])
        //                     ->where('work_date', $date)
        //                     ->get();

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
    public function getUserDailyAttendance($userId, $date)
    {
        return Attendance::with(['user', 'breakTimes'])
                            ->where('user_id', $userId)
                            ->where('work_date', $date)
                            ->first();
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

    public function updateAttendance(array $attendance, array $formatCarbonDate, $targetAttendance): Attendance
    {
        return DB::transaction(function () use ($attendance, $formatCarbonDate, $targetAttendance) {
            // 休憩
            foreach ($attendance['break_in'] as $id => $breakIn) {
                if //(!is_null($breakIn) && !is_null($attendance['break_out'][$id]))
                ($id !== AttendanceStatus::DRAFT->value && is_null($attendance['break_in'][$id]) && is_null($attendance['break_out'][$id])) {
                    // 削除
                    BreakTime::find($id)->delete();
                } else {
                    // 何かしら入力があるとき
                    BreakTime::updateOrCreate(
                        ['id' => $id,  'attendance_id' => $attendance['attendance_id']],
                        [
                            'clock_in'  => $formatCarbonDate['break_in'][$id],
                            'clock_out' => $formatCarbonDate['break_out'][$id],
                        ]
                    );
                }
            }

            // 勤怠日
            if (is_null($targetAttendance)) {
                // 新規登録
                return Attendance::create([
                    'user_id'      => $attendance['user_id'],
                    'work_date'    => $formatCarbonDate['work_day'],
                    'created_by'   => Auth::id(),
                    'clock_in'     => $formatCarbonDate['work_in'],
                    'clock_out'    => $formatCarbonDate['work_out'],
                    'note'         => $attendance['note'],
                    'corrected_by' => Auth::id(),
                    'approved_by'  => Auth::id(),
                    'approved_at'  => now(),
                    'status'       => AttendanceStatus::APPROVED->value,
                ]);
            }

            // 更新
            $targetAttendance->clock_in = $formatCarbonDate['work_in'];
            $targetAttendance->clock_out = $formatCarbonDate['work_out'];
            $targetAttendance->note = $attendance['note'];
            $targetAttendance->corrected_by = Auth::id();
            $targetAttendance->approved_by = Auth::id();
            $targetAttendance->approved_at =  now();
            $targetAttendance->status =  AttendanceStatus::APPROVED->value;
            $targetAttendance->save();

            return $targetAttendance;
        });
    }
}
