<?php

namespace App\Http\Controllers;

use App\Services\AttendanceCalculatorService;
use App\Services\AttendanceUpdateService;
use App\Services\AttendanceFormatterService;
use App\Services\AttendanceResolverService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
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
    public function index()
    {
        [
            'attendanceStatus' => $attendanceStatus,
            'attendance' => $attendance,
        ]
        = $this->attendanceResolverService->getUserAttendanceStatus(now()->format('Y-m-d'));

        $now = now();
        $time = $now->copy()->format('H:i');
        $date = $now->copy()->format('Y年n月j日') . '(' . $now->copy()->isoFormat('ddd') . ')';

        return view('user.index', compact('attendanceStatus', 'attendance', 'time', 'date'));
    }

    public function logAttendance(Request $request)
    {
        $attendanceId = $request->input('attendance_id');
        $action = $request->input('action');

        try {
            if (is_null($action)) {
                throw new \Exception();
            }
            if ($attendanceId) {
                // 修正権限があるか確認
                $attendance = Attendance::findOrFail($attendanceId);
                $this->authorize('update', $attendance);
            }
            $this->attendanceUpdateService->attendanceRegister($action, $attendanceId);
            return redirect()->route('index');
        } catch (\Exception $e) {
            return redirect()->route('index')
                            ->with('alert', 'システムエラーが発生しました')
                            ->with('alert-type', 'alert--error');
        }
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
            $attendance = Attendance::findOrFail($attendanceId);
            // 閲覧権限があるか確認
            $this->authorize('view', $attendance);
            [
                'workTimes' => $workTimes,
                'breakTimes' => $breakTimes,
                'workDate' => $workDate,
                'currentAttendanceStatus' => $currentAttendanceStatus,
                'note' => $note,
            ]
            = $this->attendanceCalculatorService->getUserDailyAttendance($attendanceId, null);
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
            'attendanceId' => $attendanceId,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'currentAttendanceStatus' => $currentAttendanceStatus ?? null,
            'note' => $note ?? null,
        ]);
    }

    public function update(AttendanceRequest $request): \Illuminate\Http\RedirectResponse
    {
        $input = $request->validated();
        $hidden = $request->only('attendance_id', 'user_id', 'current_attendance_status', 'year', 'month', 'day');
        $applyAttendance = array_merge($hidden, $input);

        // 修正権限があるか確認
        if ($hidden['attendance_id']) {
            $attendance = Attendance::findOrFail($hidden['attendance_id']);
            $this->authorize('update', $attendance);
        }
        try {
            $applyAttendance = $this->attendanceUpdateService->applyAttendance($applyAttendance);

            return redirect()->route('show', ['id' => $applyAttendance->attendance_id])
                                ->with('alert', '勤怠情報を修正しました')
                                ->with('alert-type', 'alert--success');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error($e);
            return redirect()->route('index')
                            ->with('alert', 'システムエラーが発生しました')
                            ->with('alert-type', 'alert--error');
        } catch (\RuntimeException $e) {
            return redirect()->route('show', ['id' => $hidden['attendance_id']])
                            ->with('alert', $e->getMessage())
                            ->with('alert-type', 'alert--error');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->route('index')
                            ->with('alert', 'システムエラーが発生しました')
                            ->with('alert-type', 'alert--error');
        }
    }
}
