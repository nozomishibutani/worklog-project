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
use App\Models\AttendanceApplication;
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
        if (auth('admin')->check()) {
            switch ($mode) {
                case ApprovalStatus::PENDING->value:
                    //$attendanceApplications = AttendanceApplication::with('attendance.user', 'attendanceHistory')
                    $attendanceApplications = AttendanceApplication::with('attendance.user')
                                                                    ->whereNull('approved_by')
                                                                    ->whereNull('approved_at')
                                                                    ->orderBy('applied_at', 'asc')
                                                                    ->get();

                    return view('application_index', compact('attendanceApplications'));

                    break;
                case ApprovalStatus::APPROVED->value:
                    $attendanceApplications = AttendanceApplication::with('attendance.user')
                                                                    ->whereNotNull('approved_by')
                                                                    ->whereNotNull('approved_at')
                                                                    ->orderBy('approved_at', 'desc')
                                                                    ->get();

                    // $attendances = Attendance::whereHas('latestAttendanceApplication', function ($q) {
                    //     $q->whereNotNull('approved_by')
                    //     ->whereNotNull('approved_at');
                    // })
                    // ->with('latestAttendanceApplication')
                    // ->get();

                    // 同日に複数回承認していても表示は最新の勤怠のみ
                    // $attendances = Attendance::whereHas('latestAttendanceApplication', function ($q) {
                    //     $q->whereNotNull('approved_by')
                    //     ->whereNotNull('approved_at');
                    // })
                    // ->with('latestAttendanceApplication')
                    // ->orderByDesc(
                    //     AttendanceApplication::select('approved_at')
                    //                 ->whereColumn('attendance_applications.attendance_id', 'attendances.id')
                    //                 ->whereNotNull('approved_by')
                    //                 ->whereNotNull('approved_at')
                    //                 ->latest('approved_at')
                    //                 ->limit(1)
                    // )
                    // ->get();

                    //return view('application_index', compact('attendances'));
                    //return view('application_index', compact('attendanceApplications'));
                    break;
                default:
                    $attendanceApplications = null;
                    break;
            }

            return view('application_index', compact('attendanceApplications'));

        }

        if (auth('web')->check()) {
            $attendances = null;
            switch ($mode) {
                case ApprovalStatus::PENDING->value:
                    $attendances = Attendance::with('latestAttendanceChange')
                                                        ->where('user_id', Auth::id())
                                                        ->get();
                    break;

                case ApprovalStatus::APPROVED->value:
                    $attendances = AttendanceApproval::with('AttendanceChange')
                                            ->where('user_id', Auth::id())
                                            ->orderBy('approved_at', 'desc')
                                            ->get();
                    break;
            }
            return view('application_index', compact('attendances', 'approvalStatus'));
        }
    }

    public function update(AttendanceRequest $request): \Illuminate\Http\RedirectResponse
    {
        $tmp = $request->validated();
        $attendance = $request->only('user_id', 'attendance_id', 'current_attendance_status', 'year', 'month', 'day');
        $attendance = array_merge($attendance, $tmp);

        $result = $this->attendanceUpdateService->applyAttendance($attendance);

        $isAdmin = auth('admin')->check();

        $routePrefix = $isAdmin ? Role::ADMIN->value . '.' : '';
        $routeName = $isAdmin ? $routePrefix . 'show' : 'show';

        if ($result) {
            return redirect()
                ->route($routeName, ['id' => $result->attendance_id])
                ->with('alert', '勤怠情報を修正しました')
                ->with('alert-type', 'alert-success');
        }

        return redirect()
            ->route($isAdmin ? $routePrefix . 'index' : 'user.index')
            ->with('alert', 'システムエラーが発生しました')
            ->with('alert-type', 'alert-error');

    }
}
