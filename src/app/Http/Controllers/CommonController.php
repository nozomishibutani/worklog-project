<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Services\AttendanceCalculatorService;
use App\Services\AttendanceUpdateService;
use App\Services\AttendanceFormatterService;
use App\Services\AttendanceResolverService;
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
    protected AttendanceResolverService $attendanceResolverService;

    public function __construct(
        AttendanceCalculatorService $attendanceCalculatorService,
        AttendanceUpdateService $attendanceUpdateService,
        AttendanceFormatterService $attendanceFormatterService,
        AttendanceResolverService $attendanceResolverService,
    ) {
        $this->attendanceCalculatorService = $attendanceCalculatorService;
        $this->attendanceUpdateService = $attendanceUpdateService;
        $this->attendanceFormatterService = $attendanceFormatterService;
        $this->attendanceResolverService =  $attendanceResolverService;
    }

    public function applicationIndex(Request $request)
    {
        $mode = $request->query('mode');
        $approvalStatus = ApprovalStatus::tryFrom($mode);
        $attendances = null;

        if (is_null($mode)) {
            return view('application_index', compact('attendances', 'approvalStatus'));
        }
        if (session('role') === Role::ADMIN->value) {
            $attendances = $this->attendanceResolverService->getAllUserApplicationIndex($mode);
        } elseif (session('role') === Role::USER->value) {
            $attendances =  $this->attendanceResolverService->getUserApplicationIndex($mode);
        }

        return view('application_index', compact('attendances', 'approvalStatus'));
    }
}
