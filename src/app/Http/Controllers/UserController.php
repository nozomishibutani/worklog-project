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

use function Symfony\Component\Clock\now;

class UserController extends Controller
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
    public function index()
    {
        [
            'attendanceStatus' => $attendanceStatus,
            'attendance' => $attendance,
        ] = $this->attendanceCalculatorService->getUserAttendanceStatus(now()->format('Y-m-d'));

        $time = now()->format('H:i');
        $day = $this->attendanceFormatterService->addDay(now(), 'Y年n月j日');

        return view('user.index', compact('attendanceStatus', 'attendance', 'time', 'day'));
    }

    public function logAttendance(Request $request)
    {
        $attendanceId = $request->input('attendance_id');
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            $result =  $this->attendanceUpdateService->attendanceRegister($action, $attendanceId);
            if ($result) {
                return redirect()->route('index');
            }
        }

        return redirect()->route('index')
                                        ->with('alert', 'システムエラーが発生しました')
                                        ->with('alert-type', 'alert-error');
    }
    public function monthlyIndex(Request $request)
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
        = $this->attendanceCalculatorService->getUserMonthlyAttendances(Auth::id(), $startOfMonth);

        return view('user.monthly_index', [
            'userId' => Auth::id(),
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'route' => 'show',
            'date' => [
                'label' => $startOfMonth->copy()->format('Y/m'),
                'prev' => $startOfMonth->copy()->subMonth()->format('Ym'),
                'next' => $startOfMonth->copy()->addMonth()->format('Ym'),
            ],
        ]);
    }

    public function show(Request $request, $attendanceId = null): \Illuminate\View\View
    {
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
            $user = Auth::user();
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
            //'route' => Role::ADMIN->value. '.show',
            'attendanceApplication' => isset($attendance) ? $attendance?->latestAttendanceApplication : null,
        ]);
    }

}
