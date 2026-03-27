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

    public function index(): \Illuminate\View\View
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
        $date = session('admin_' . TYPE::PERSONALLY->value);
        $date = Carbon::parse($date);
        //session()->forget('admin_' . TYPE::PERSONALLY->value);

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
        = $this->attendanceCalculatorService->getUserMonthlyAttendances($userId, $date);

        return view('admin/user_monthly_index', [
            'userId' => $userId,
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'date' => [
                'label' => $date->copy()->format('Y/m'),
                'prev' => $date->copy()->subMonth()->format('Ymd'),
                'next' => $date->copy()->addMonth()->format('Ymd'),
            ],
        ]);
    }

    public function update(Request $request): bool|\Illuminate\Http\RedirectResponse
    {
        $attendance = $request->input();

        $res = $this->attendanceUpdateService->updateAttendance($attendance);
        if ($res) {

            return redirect()->route('admin.show', ['id' => $attendance['user_id']])
                            ->with('alert', '修正が完了しました。')
                            ->with('alert-type', 'alert-success');

        }

        return redirect()->route('admin.index')
                    ->with('alert', 'システムエラーが発生しました')
                    ->with('alert-type', 'alert-error');


        //dd($attendance);
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
    public function setSession($date, $to = null, $userId = null): \Illuminate\Http\RedirectResponse
    {
        if (!$date) {
            return redirect()->route('admin.index')
            ->with('alert', 'システムエラーが発生しました')
            ->with('alert-type', 'alert-error');
        }

        switch ($to) {
            case TYPE::MONTHLY->value:
                session(['admin_' . TYPE::MONTHLY->value => $date]);
                return redirect()->route('admin.monthly.index', ['id' => $userId]);

            case TYPE::PERSONALLY->value:
                if (session()->has('admin_' . TYPE::DAILY->value)) {
                    session()->forget('admin_' . TYPE::DAILY->value);
                }
                session(['admin_' . TYPE::PERSONALLY->value => $date]);
                return redirect()->route('admin.show', ['id' => $userId]);

            default:
                session(['admin_' . TYPE::DAILY->value => $date]);
                return redirect()->route('admin.index');
        }
    }
}
