<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
//use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Enums\Type;
use App\Enums\AttendanceStatus;

class AdminController extends Controller
{
    protected AttendanceService $attendanceService;
    //protected ApprovalService $approvalService;
    protected $today;

    //public function __construct(AttendanceService $attendanceService, ApprovalService $approvalService)
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
        //$this->approvalService = $approvalService;
        $this->today = Carbon::today();
    }

    public function index()
    {
        //$date = Carbon::today();
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceService->getByPeriod($this->today);

        return view('admin/index', [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'date' => $this->today,
        ]);
    }

    public function changeDate(Request $request)
    {
        $date = $request->input('change_date');

        if (!$date) {
            return redirect()->route('admin.index')
            ->with('alert', 'システムエラーが発生しました')
            ->with('alert-type', 'alert-error');
        }

        $date = Carbon::parse($date);

        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceService->getByPeriod($date);

        return view('admin/index', [
                    'workTimes' => $workTimes,
                    'breakTimes' => $breakTimes,
                    'date' => $date,
                ]);

    }

    public function show($userId)
    {

        dd($userId);
        //$detail = $this->attendanceService->getAttendanceDetail($userId);
        return Attendance::with(['user', 'breakTimes'])
                            ->whereDate('work_date', $date)
                            ->get();
    }

    public function userIndex()
    {
        $users = User::all();
        return view('admin/user_index', compact('users'));
    }

    public function userMonthlyIndex()
    {
        //$date = Carbon::today();
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceService->getByPeriod($this->today, Type::TYPE_MONTHLY);

        return view('admin/user_monthly_index', [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'date' => $this->today,
        ]);
    }

    public function userShow()
    {
        //
    }

    /**
     * 承認待ち勤怠を取得
     */
    public function getPending() : array
    {
        return Attendance::where('status', AttendanceStatus::PENDING)->get();
    }

    /**
     * 承認済み勤怠を取得
     */
    public function getApproved()  : array
    {
        return Attendance::where('status', AttendanceStatus::APPROVED)->get();
    }
}
