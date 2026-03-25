<?php

namespace App\Repositories;

use App\Models\Attendance;

class AttendanceRepository
{
    /**
     * 勤怠を取得
     */
    public function getAttendances($start, $end)
    {
        return Attendance::with(['user', 'breakTimes'])
                            ->whereBetween('work_date', [$start, $end])
                            ->get();
    }
}