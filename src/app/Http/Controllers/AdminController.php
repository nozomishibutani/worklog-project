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

    //public function __construct(AttendanceService $attendanceService, ApprovalService $approvalService)
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
        //$this->approvalService = $approvalService;
    }

    public function index()
    {
        // sessionを受け取る
        if (session()->has('admin_' . TYPE::DAILY->value)) {
            $date = session('admin_' . TYPE::DAILY->value);
            $date = Carbon::parse($date);
            //session()->forget('admin_' . TYPE::DAILY->value);
        } else {
            $date = carbon::today();
        }

        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceService->getAllUserDailyAttendances($date);

        return view('admin/index', [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'date' => $date,
        ]);

    }

    public function show($userId)
    {
        // sessionを受け取る
        //if (session()->has('admin_' . TYPE::PERSONALLY->value)) {
        $date = session('admin_' . TYPE::PERSONALLY->value);
        $date = Carbon::parse($date);
        //session()->forget('admin_' . TYPE::PERSONALLY->value);
        //}

        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceService->getUserDailyAttendance($userId, $date);
//dd($workTimes);
        $keys = array_keys($workTimes);
        $date = Carbon::parse($keys[0]);
        $year = $date->format('Y年');
        $monthDay = $date->format('n月j日');
//dd($date);
        return view('admin/show', [
            'userId' => $userId,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'date' => $date,
            'year' => $year,
            'monthDay' => $monthDay,
        ]);
    }

    public function userIndex()
    {
        $users = User::all();
        return view('admin/user_index', compact('users'));
    }

    public function userMonthlyIndex($userId)
    {
        // sessionを受け取る
        if (session()->has('admin_' . TYPE::MONTHLY->value)) {
            $date = session('admin_' . TYPE::MONTHLY->value);
            $date = Carbon::parse($date);
            //session()->forget('admin_' . TYPE::MONTHLY->value);
        } else {
            $date = carbon::today();
        }

        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceService->getUserMonthlyAttendances($userId, $date);

        return view('admin/user_monthly_index', [
            'userId' => $userId,
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'date' => $date,
        ]);
    }

    public function setDailySession($date)
    {
        if (!$date) {
            return redirect()->route('admin.index')
            ->with('alert', 'システムエラーが発生しました')
            ->with('alert-type', 'alert-error');
        }

        session(['admin_' . TYPE::DAILY->value => $date]);
        return redirect()->route('admin.index');
    }

    public function setMonthlySession($to, $userId, $date)
    {

        if (!$to || !$userId || !$date) {
            return redirect()->route('admin.index')
            ->with('alert', 'システムエラーが発生しました')
            ->with('alert-type', 'alert-error');
        }

        switch ($to) {
            case TYPE::MONTHLY->value:
                session(['admin_' . TYPE::MONTHLY->value => $date]);
                return redirect()->route('admin.monthly.index', ['id' => $userId]);

            case TYPE::PERSONALLY->value:
                session(['admin_' . TYPE::PERSONALLY->value => $date]);
                return redirect()->route('admin.show', ['id' => $userId]);

            default:
                return redirect()->route('admin.index')
                            ->with('alert', 'システムエラーが発生しました')
                            ->with('alert-type', 'alert-error');
        }
    }


    public function userShow()
    {
        //
    }

    /**
     * 承認待ち勤怠を取得
     */
    public function getPending(): array
    {
        return Attendance::where('status', AttendanceStatus::PENDING)->get();
    }

    /**
     * 承認済み勤怠を取得
     */
    public function getApproved(): array
    {
        return Attendance::where('status', AttendanceStatus::APPROVED)->get();
    }
}
