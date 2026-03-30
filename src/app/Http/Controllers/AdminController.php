<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Services\AttendanceCalculatorService;
use App\Services\AttendanceUpdateService;
//use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Enums\Type;
use App\Enums\AttendanceStatus;
use App\Http\Requests\AttendanceRequest;

class AdminController extends Controller
{
    protected AttendanceCalculatorService $attendanceCalculatorService;
    protected AttendanceUpdateService $attendanceUpdateService;
    //protected ApprovalService $approvalService;


    //public function __construct(AttendanceCalculatorService $attendanceCalculatorService, ApprovalService $approvalService)
    public function __construct(AttendanceCalculatorService $attendanceCalculatorService, AttendanceUpdateService $attendanceUpdateService)
    {
        $this->attendanceCalculatorService = $attendanceCalculatorService;
        $this->attendanceUpdateService = $attendanceUpdateService;
        //$this->approvalService = $approvalService;
    }
    public function clearSession()
    {
        // 隠しセッション削除ボタン
        if (session()->has('admin_' . TYPE::DAILY->value)) {
            session()->forget('admin_' . TYPE::DAILY->value);
        }
        if (session()->has('admin_' . TYPE::MONTHLY->value)) {
            session()->forget('admin_' . TYPE::MONTHLY->value);
        }

        if (session()->has('admin_' . TYPE::PERSONALLY->value)) {
            session()->forget('admin_' . TYPE::PERSONALLY->value);
        }
        return back();
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
        = $this->attendanceCalculatorService->getAllUserDailyAttendances($date);

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

    public function show($userId): \Illuminate\View\View
    {
        // sessionを受け取る
        $date = session('admin_date');
        $date = Carbon::parse($date);

        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceCalculatorService->getUserDailyAttendance($userId, $date);

        $workDate = [
            'year'  => $date->year,
            'month' => $date->month,
            'day'   => $date->day,
        ];

        $breakTimeCount = count($breakTimes);
        //dd($breakTimes);
        return view('admin/show', [
            'userId' => $userId,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'breakTimeCount' => $breakTimeCount,
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
            $startOfMonth = Carbon::createFromFormat('Ymd', $date . '01');
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

    public function update(AttendanceRequest $request): \Illuminate\Http\RedirectResponse
    {
        $tmp = $request->validated();
        $attendance = $request->only('user_id', 'attendance_id', 'year', 'month', 'day');
        $attendance = array_merge($attendance, $tmp);

        $result = $this->attendanceUpdateService->updateAttendance($attendance);
        if ($result) {

            return redirect()->route('admin.show', ['id' => $result->user_id])
                            ->with('alert', '勤怠情報を修正しました')
                            ->with('alert-type', 'alert-success');
        }

        return redirect()->route('admin.index')
                    ->with('alert', 'システムエラーが発生しました')
                    ->with('alert-type', 'alert-error');
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

    /**
     * 指定された勤怠の日付をsessionに保存する
     *
     * @param string $date
     * @param string|null $to 遷移先
     * @param int|null $userId ユーザーid
     * @return @return \Illuminate\Http\RedirectResponse
     */
    public function setSession($userId, $date): \Illuminate\Http\RedirectResponse
    {
        if (!$userId || !$date) {
            return redirect()->route('admin.index')
            ->with('alert', 'システムエラーが発生しました')
            ->with('alert-type', 'alert-error');
        }
        session(['admin_date' => $date]);

        return redirect()->route('admin.show', ['id' => $userId]);
    }

}
