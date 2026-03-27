<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

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

    public function updateAttendance(array $attendance): bool
    {
        dd($attendance['break_out'][4]);

        // 休憩
        foreach ($attendance['break_in'] as $id => $breakTime) {

            if ($id === 'create') {
                // 新規作成
                BreakTime::create([
                                'user_id' => $breakTime['user_id'],
                                'attendance_id' => $breakTime['attendance_id'],
                                'clock_in' => $breakTime['create'],
                                'clock_out' => $breakTime['break_out']['create'],
                            ]);
            } elseif ($breakTime[$id]) {
                // 更新
                $target = BreakTime::find($id);
                $target->clock_in = $breakTime['create'];
                $target->clock_out = $breakTime['break_out']['create'];

                $target->save();
                // BreakTime::where('id', $id)
                //         ->where('user_id', $breakTime['user_id'])
                //         ->where('attendance_id', $breakTime['attendance_id'])
                //         ->updated([
                //                 'clock_in' => $breakTime['break_in']['create'],
                //                 'clock_out' => $breakTime['break_out']['create'],
                //                 //'updated_at' => now(),
                //             ]);

            } elseif (!$breakTime[$id]) {
                // 削除
                BreakTime::where('id', $id)
                        ->where('user_id', $breakTime['user_id'])
                        ->where('attendance_id', $breakTime['attendance_id'])->delete();
            }

            $target = Attendance::find($attendance['attendance_id']);
            $target->clock_in = $attendance['work_in'];
            $target->clock_out = $attendance['work_out'];
            $target->note = $attendance['note'];
            $target->corrected_by = Auth::id();
            $target->status = AttendanceStatus::PENDING->value;

            return $target->save();
        }

    }

}
