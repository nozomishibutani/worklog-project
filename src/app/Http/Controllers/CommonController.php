<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Services\AttendanceCalculatorService;
use App\Services\AttendanceUpdateService;
use App\Services\AttendanceFormatterService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Enums\Type;
use App\Http\Requests\AttendanceRequest;
use Illuminate\Support\Facades\Auth;
use App\Enums\ApprovalStatus;
use App\Enums\Role;
use App\Models\AttendanceApproval;
use App\Models\AttendanceChange;

class CommonController extends Controller
{
    protected AttendanceCalculatorService $attendanceCalculatorService;
    protected AttendanceUpdateService $attendanceUpdateService;
    protected AttendanceFormatterService $attendanceFormatterService;

    public function __construct(
        AttendanceCalculatorService $attendanceCalculatorService,
        AttendanceUpdateService $attendanceUpdateService,
        AttendanceFormatterService $attendanceFormatterService,
    ) {
        $this->attendanceCalculatorService = $attendanceCalculatorService;
        $this->attendanceUpdateService = $attendanceUpdateService;
        $this->attendanceFormatterService = $attendanceFormatterService;
    }
    public function applicationIndex(Request $request)
    {
        $mode = $request->query('mode');
        $approvalStatus = ApprovalStatus::tryFrom($mode);
        $attendances = null;
        if (auth('admin')->check()) {
            switch ($mode) {
                case ApprovalStatus::PENDING->value:
                    $attendances = AttendanceChange::doesntHave('attendanceApproval')
                                                    ->orderBy('applied_at', 'asc')
                                                    ->get();
                    break;
                case ApprovalStatus::APPROVED->value:
                    $attendances = AttendanceApproval::with('attendanceChange')
                                                    ->orderBy('approved_at', 'desc')
                                                    ->get();
                    break;
            }
        }
        if (auth('web')->check()) {
            switch ($mode) {
                // 同日に複数回承認・修正を繰り返していても最新1件のみ表示
                case ApprovalStatus::PENDING->value:
                    $attendances = Attendance::with('latestAttendanceChange.attendanceApproval')
                                                ->whereHas('latestAttendanceChange')
                                                ->doesntHave('latestAttendanceChange.attendanceApproval')
                                                ->where('user_id', Auth::id())
                                                ->orderBy(
                                                    AttendanceChange::select('applied_at')
                                                    ->whereColumn('attendance_changes.attendance_id', 'attendances.id')
                                                    ->latest()
                                                    ->limit(1)
                                                )
                                                ->get();
                    break;

                case ApprovalStatus::APPROVED->value:
                    $attendances = Attendance::with('latestAttendanceChange.attendanceApproval')
                                                ->whereHas('latestAttendanceChange.attendanceApproval')
                                                ->where('user_id', Auth::id())
                                                ->orderBy(
                                                    AttendanceChange::select('applied_at')
                                                    ->whereColumn('attendance_changes.attendance_id', 'attendances.id')
                                                    ->latest()
                                                    ->limit(1)
                                                )
                                                ->get();
                    break;
            }
        }
        return view('application_index', compact('attendances', 'approvalStatus'));
    }
}
