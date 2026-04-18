<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\AttendanceChange;
use App\Models\BreakTimeChange;
use App\Models\BreakTime;
use App\Models\AttendanceApproval;
use App\Models\BreakTimeApproval;

class AttendanceRepository
{
    public function createAttendance($attendance): Attendance
    {
        return  Attendance::create($attendance);
    }

    public function createAttendanceChange($attendance): AttendanceChange
    {
        return  AttendanceChange::create($attendance);
    }

    public function createAttendanceApproval($attendance): AttendanceApproval
    {
        return  AttendanceApproval::create($attendance);
    }

    public function createBreakTime(array $breakTime): BreakTime
    {
        return BreakTime::create($breakTime);
    }

    public function createBreakTimeChanges(array $breakTime): BreakTimeChange
    {
        return BreakTimeChange::create($breakTime);
    }

    public function createBreakTimeApprovals(array $breakTime): BreakTimeApproval
    {
        return BreakTimeApproval::create($breakTime);
    }

    public function updateAttendance($targetAttendance): bool
    {
        return  $targetAttendance->save();
    }

    public function updateBreakTime(array $conditions, array $data): int
    {
        return BreakTime::where($conditions)->update($data);
    }
}
