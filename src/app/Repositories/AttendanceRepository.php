<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\User;
use App\Models\BreakTime;
use App\Enums\ApprovalStatus;
use App\Models\AttendanceApplication;
use App\Models\AttendanceHistory;
use App\Models\BreakTimeHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceRepository
{
    public function getUserMonthlyAttendances($userId, $start, $end): Collection
    {
        return Attendance::with(['user', 'breakTimes'])
                            ->where('user_id', $userId)
                            ->whereBetween('work_date', [$start, $end])
                            ->get();
    }

    public function createAttendance($attendance): Attendance
    {
        return  Attendance::create($attendance);
    }

    public function updateAttendance($targetAttendance): bool
    {
        return  $targetAttendance->save();
    }

    public function createBreakTime(array $breakTime): BreakTime
    {
        return BreakTime::create($breakTime);
    }

    public function updateBreakTime(array $conditions, array $data): int
    {
        return BreakTime::where($conditions)->update($data);
    }

    public function deleteBreakTime($breakTimeId): bool
    {
        return  BreakTime::find($breakTimeId)->delete();
    }

    public function createAttendanceApplication($attendanceApplication): AttendanceApplication
    {
        return AttendanceApplication::create($attendanceApplication);
    }

    public function createAttendanceHistory($attendanceHistory): AttendanceHistory
    {
        return  AttendanceHistory::create($attendanceHistory);
    }

    public function createBreakTimeHistory($breakTimeHistory): BreakTimeHistory
    {
        return  BreakTimeHistory::create($breakTimeHistory);
    }

    public function approveAttendanceApplication($attendanceApplication): bool
    {
        return  $attendanceApplication->save();
    }
}
