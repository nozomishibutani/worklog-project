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
        if (auth('admin')->check()) {
            $mode = $request->query('mode');
            switch ($mode) {
                case ApprovalStatus::PENDING->value:
                    $attendanceApplications = AttendanceApplication::with('attendance.user')->where('status', ApprovalStatus::PENDING->value)->get();
                    break;
                case ApprovalStatus::APPROVED->value:
                    $attendanceApplications = AttendanceApplication::with('attendance.user')
                                                                    ->where('status', ApprovalStatus::APPROVED->value)
                                                                    ->orderBy('approved_at', 'desc')
                                                                    ->get();
                    break;
                default:
                    $attendanceApplications = null;
                    break;
            }
            return view('admin/application_index', compact('attendanceApplications'));
        }

        if (auth('web')->check()) {
            $mode = $request->query('mode');
            switch ($mode) {
                case ApprovalStatus::PENDING->value:

                    $attendanceApplications = AttendanceApplication::where('status', ApprovalStatus::PENDING->value)
                                                                    ->whereRelation('attendance', 'user_id', Auth::id())
                                                                    ->with('attendance')
                                                                    ->get();
                    break;
                case ApprovalStatus::APPROVED->value:

                    $attendanceApplications = AttendanceApplication::where('status', ApprovalStatus::APPROVED->value)
                                                                                        ->whereRelation('attendance', 'user_id', Auth::id())
                                                                                        ->with('attendance')
                                                                                        ->get();
                    break;

                default:
                    $attendanceApplications = null;
                    break;
            }

            return view('admin/application_index', compact('attendanceApplications'));

        }



    }
    public function update(AttendanceRequest $request): \Illuminate\Http\RedirectResponse
    {
        $tmp = $request->validated();
        $attendance = $request->only('user_id', 'attendance_id', 'year', 'month', 'day');
        $attendance = array_merge($attendance, $tmp);

        $result = $this->attendanceUpdateService->updateAttendance($attendance);

        $route = auth('admin')->check() ? Role::ADMIN->value . '.' : null;

        if ($result) {
            return redirect()->route($route . 'show', ['id' => $result->attendance_id])
                            ->with('alert', '勤怠情報を修正しました')
                            ->with('alert-type', 'alert-success');
        }
        return redirect()->route($route . 'index')
                    ->with('alert', 'システムエラーが発生しました')
                    ->with('alert-type', 'alert-error');
    }
}
