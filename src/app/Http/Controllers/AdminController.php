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
use App\Enums\Role;

class AdminController extends Controller
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

    public function index(Request $request): \Illuminate\View\View
    {
        $date = $request->query('date');

        if ($date) {
            $date = Carbon::parse($date);
        } else {
            $date = carbon::today();
        }

        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceCalculatorService->getUserDailyAttendances($date);

        return view('admin/index', [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'date' => [
                'title' => $date->copy()->format('Y年m月d日'),
                'label' => $date->copy()->format('Y/m/d'),
                'prev' => $date->copy()->subDay()->format('Ymd'),
                'next' => $date->copy()->addDay()->format('Ymd'),
                'detail' => $date->copy()->format('Ymd'),
            ],
        ]);
    }

    public function show(Request $request, $attendanceId = null): \Illuminate\View\View
    {
        $userId = $request->query('user_id');
        $date =  $request->query('date');

        if ($attendanceId) {
            $attendance = Attendance::with(['user', 'breakTimes'])->find($attendanceId);

            [
                'workTimes' => $workTimes,
                'breakTimes' => $breakTimes,
                'workDate' => $workDate,
            ]
            = $this->attendanceCalculatorService->getUserDailyAttendance($attendance);

        } else {
            $user = User::find($userId);
            [
                'workTimes' => $workTimes,
                'breakTimes' => $breakTimes,
                'workDate' => $workDate,
            ]
            = $this->attendanceFormatterService->createDailyEmptyRecord($user, $date);
        }

        return view('show', [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'route' => Role::ADMIN->value. '.show',
            'attendanceApplication' => isset($attendance) ? $attendance?->latestAttendanceApplication : null,
        ]);
    }

    public function userIndex(): \Illuminate\View\View
    {
        $users = User::all();
        return view('admin/user_index', compact('users'));
    }

    public function userMonthlyIndex(Request $request, $userId)
    {
        $date = $request->query('date');

        if ($date) {
            $startOfMonth = Carbon::createFromFormat('Ymd', $date . '01')->startOfMonth();
        } else {
            $startOfMonth = carbon::today()->startOfMonth();
        }

        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceCalculatorService->getUserMonthlyAttendances($userId, $startOfMonth);

        return view('admin/user_monthly_index', [
            'userId' => $userId,
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'date' => [
                'label' => $startOfMonth->copy()->format('Y/m'),
                'prev' => $startOfMonth->copy()->subMonth()->format('Ym'),
                'next' => $startOfMonth->copy()->addMonth()->format('Ym'),
            ],
        ]);
    }

    public function showForApproval($applicationId)
    {
        $application = AttendanceApplication::with('attendance.user', 'attendance.breakTimes', 'attendanceHistory')->find($applicationId);
        if (is_null($application)) {
            return redirect()->route('admin.index')
                                ->with('alert', 'システムエラーが発生しました')
                                ->with('alert-type', 'alert-error');
        }
        if ($application->isApproved()) {
            // 履歴表示
            $attendance = $application->attendanceHistory;

        } else {
            // 最新
            $attendance = $application->attendance;
        }

        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
        ]
        = $this->attendanceCalculatorService->getUserDailyAttendance($attendance);

        return view('admin/approval', [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'attendanceApplication' => $application,
        ]);
    }

    public function approve($attendanceApplicationId)
    {
        $result = $this->attendanceUpdateService->approveAttendanceApplication($attendanceApplicationId);

        if ($result) {
            return redirect()->route('application.index', ['mode' => 'approved'])
                                            ->with('alert', '承認が完了しました')
                                            ->with('alert-type', 'alert-success');
        }
        return redirect()->route('admin.index')
                                ->with('alert', 'システムエラーが発生しました')
                                ->with('alert-type', 'alert-error');

    }
}
