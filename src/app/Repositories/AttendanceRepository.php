<?php

namespace App\Repositories;

use App\Models\Attendance;

class AttendanceRepository
{
    /**
     * 全ユーザーの勤怠を取得
     */
    public function getAllUserAttendances($date)
    {
        return Attendance::with(['user', 'breakTimes'])
                            ->where('work_date', $date)
                            ->get();
    }
}