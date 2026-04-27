<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Models\User;
use App\Services\AttendanceCalculatorService;
use App\Services\AttendanceUpdateService;
use App\Services\AttendanceFormatterService;
use App\Services\AttendanceResolverService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Requests\AttendanceRequest;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
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
                'title' => $date->copy()->format('Y年n月j日'),
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
            [
                'workTimes' => $workTimes,
                'breakTimes' => $breakTimes,
                'workDate' => $workDate,
                'currentAttendanceStatus' => $currentAttendanceStatus,
                'note' => $note,
            ]
            = $this->attendanceCalculatorService->getUserDailyAttendance($attendanceId, null);
        } else {
            $user = User::findOrFail($userId);
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
                'export' => $startOfMonth->copy()->format('Ym'),
            ],
        ]);
    }

    public function showForApproval($attendanceChangeId): \Illuminate\View\View
    {
        [
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'currentAttendanceStatus' => $currentAttendanceStatus,
            'note' => $note,
        ]
        = $this->attendanceCalculatorService->getUserDailyAttendance(null, $attendanceChangeId);

        return view('admin/approval', [
            'attendanceChangeId' => $attendanceChangeId,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
            'workDate' => $workDate,
            'currentAttendanceStatus' => $currentAttendanceStatus,
            'note' => $note,
        ]);
    }

    public function approve(Request $request): \Illuminate\Http\RedirectResponse
    {
        $attendanceChangeId = $request->input('attendance_change_id');
        try {
            $this->attendanceUpdateService->approveAttendance($attendanceChangeId, Auth::id());
            return redirect()->route('application.index', ['mode' => ApprovalStatus::APPROVED->value])
                            ->with('alert', '承認が完了しました')
                            ->with('alert-type', 'alert--success');
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->route('application.index', ['mode' => 'approved'])
                                    ->with('alert', 'システムエラーが発生しました')
                                    ->with('alert-type', 'alert--error');
        }
    }

    public function update(AttendanceRequest $request): \Illuminate\Http\RedirectResponse
    {
        $input = $request->validated();
        $hidden = $request->only('user_id', 'attendance_id', 'current_attendance_status', 'year', 'month', 'day');
        $applyAttendance = array_merge($hidden, $input);

        try {
            // 承認前かどうか
            $count = $this->attendanceResolverService->countPendingAttendances($hidden['attendance_id']);
            if ($count !== 0) {
                throw new \RuntimeException('勤怠情報が更新されたため修正できませんでした');
            }
            $applyAttendance = $this->attendanceUpdateService->applyAttendance($applyAttendance);
            // 承認も行う
            $this->attendanceUpdateService->approveAttendance($applyAttendance->id, Auth::id());

            return redirect()->route('admin.show', ['id' => $applyAttendance->attendance_id])
                            ->with('alert', '勤怠情報を修正しました')
                            ->with('alert-type', 'alert--success');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('admin.index')
                            ->with('alert', 'システムエラーが発生しました')
                            ->with('alert-type', 'alert--error');

        } catch (\RuntimeException $e) {
            return redirect()->route('admin.show', ['id' => $hidden['attendance_id']])
                            ->with('alert', $e->getMessage())
                            ->with('alert-type', 'alert--error');

        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->route('admin.index')
                            ->with('alert', 'システムエラーが発生しました')
                            ->with('alert-type', 'alert--error');
        }
    }

    public function export($userId, $date)
    {
        $startOfMonth = Carbon::createFromFormat('Ymd', $date . '01')->startOfMonth();
        [
            'name' => $name,
            'workTimes' => $workTimes,
            'breakTimes' => $breakTimes,
        ]
        = $this->attendanceCalculatorService->getUserMonthlyAttendances($userId, $startOfMonth);

        $csvHeader = [
            '日付', '出勤', '退勤', '休憩', '合計'
        ];

        $rows = $this->attendanceFormatterService->makeCsvData($workTimes, $breakTimes);

        $response = new StreamedResponse(function () use ($rows, $csvHeader) {
            $createCsvFile = fopen('php://output', 'w');
            mb_convert_variables('SJIS-win', 'UTF-8', $csvHeader);

            fputcsv($createCsvFile, $csvHeader);

            foreach ($rows as $row) {
                mb_convert_variables('SJIS-win', 'UTF-8', $row);
                fputcsv($createCsvFile, $row);
            }

            fclose($createCsvFile);
        }, 200, [
            'Content-Type' => 'text/csv; charset=SJIS-win',
            'Content-Disposition' => "attachment; filename=\"{$name}_{$date}.csv\"",
        ]);

        return $response;
    }
}
