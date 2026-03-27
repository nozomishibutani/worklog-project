<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

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

}
