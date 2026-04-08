<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use Carbon\Carbon;
use App\Models\AttendanceApplication;
use App\Models\Attendance;
use App\Enums\Role;
use App\Enums\AttendanceStatus;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use App\Enums\ApprovalStatus;
use App\Models\AttendanceHistory;
use App\Models\BreakTimeHistory;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Log;

use function Symfony\Component\Clock\now;

class AttendanceUpdateService
{
    protected AttendanceRepository $attendanceRepository;
    protected AttendanceFormatterService $attendanceFormatterService;

    public function __construct(AttendanceFormatterService $attendanceFormatterService, AttendanceRepository $attendanceRepository)
    {
        $this->attendanceFormatterService = $attendanceFormatterService;
        $this->attendanceRepository = $attendanceRepository;
    }
    public function updateAttendance($attendance): AttendanceApplication|\Illuminate\Http\RedirectResponse
    {
        $targetAttendance = Attendance::find($attendance['attendance_id']);
        $formatCarbonDate =  $this->attendanceFormatterService->buildWorkDateFromEditAttendance($attendance);

        return DB::transaction(function () use ($targetAttendance, $attendance, $formatCarbonDate) {
            //try {
            // 履歴を作成
            // if (!is_null($targetAttendance)) {
            //     $attendanceHistory = $this->createHistory($targetAttendance, $attendance, $formatCarbonDate);
            // } else {
            //     // 空レコードの場合
            //     $attendanceHistory = null;
            // }
            // 勤怠登録
            $saveAttendance = $this->saveAttendance($targetAttendance, $attendance, $formatCarbonDate);
            // 休憩
            $this->saveBreakTimes($saveAttendance, $attendance, $formatCarbonDate);

            // 修正リクエスト作成
            return $this->createApplicationAttendance($saveAttendance, $attendance);
            // } catch (\Exception $e) {
            //     Log::error($e);
            //     $route = auth('admin')->check() ? Role::ADMIN->value . '.' : null;
            //     return redirect()
            //         ->route($route .'index')
            //         ->with('alert', 'システムエラーが発生しました')
            //         ->with('alert-type', 'alert-error');
            // }
        });
    }

    private function saveAttendance(?Attendance $targetAttendance, array $attendance, $formatCarbonDate): Attendance
    {
        // 勤怠日
        if (is_null($targetAttendance)) {
            // 新規登録
            return $this->attendanceRepository->createAttendance([
                'user_id'      => $attendance['user_id'],
                'work_date'    => $formatCarbonDate['work_in']->copy()->format('Y-m-d'),
                'created_by'   => Auth::id(),
                'clock_in'     => $formatCarbonDate['work_in'],
                'clock_out'    => $formatCarbonDate['work_out'],
            ]);
        } else {
            // 更新
            $targetAttendance->clock_in = $formatCarbonDate['work_in'];
            $targetAttendance->clock_out = $formatCarbonDate['work_out'];
            $this->attendanceRepository->updateAttendance($targetAttendance);
        }
        return $targetAttendance;
    }
    private function saveBreakTimes(?Attendance $saveAttendance, array $attendance, $formatCarbonDate): void
    {
        if (!isset($attendance['break_in'])) {
            return;
        }
        foreach ($attendance['break_in'] as $id => $breakIn) {
            if ($id !== 0 && is_null($breakIn) && is_null($attendance['break_out'][$id])) {
                // 削除
                $this->attendanceRepository->deleteBreakTime($id);
            } elseif (!is_null($breakIn)) {
                match ($id) {
                    // 新規
                    0 => $this->attendanceRepository->createBreakTime([
                        'attendance_id' => $saveAttendance->id,
                        'clock_in'  => $formatCarbonDate['break_in'][$id],
                        'clock_out' => $formatCarbonDate['break_out'][$id],
                        'created_by' => Auth::id(),
                    ]),
                    // 更新
                    default => $this->attendanceRepository->updateBreakTime(
                        ['id' => $id,  'attendance_id' => $saveAttendance->id],
                        [
                            'clock_in'  => $formatCarbonDate['break_in'][$id],
                            'clock_out' => $formatCarbonDate['break_out'][$id],
                            'updated_at' => now(),
                        ]
                    ),
                };
            }
        }
    }

    private function createApplicationAttendance(Attendance $saveAttendance, array $attendance): AttendanceApplication
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->isAdmin();
        return $this->attendanceRepository->createAttendanceApplication([
                'attendance_id' => $saveAttendance->id,
                'applied_by'    => Auth::id(),
                'applied_at'    => now(),
                'approved_by'  => $isAdmin ? Auth::id() : null,
                'approved_at'  => $isAdmin ? now() : null,
                'note'         => $attendance['note'],
            ]);
    }

    public function createHistory($targetAttendance): AttendanceHistory
    {
        $attendanceHistory = $this->attendanceRepository->createAttendanceHistory([
                        'user_id'  => $targetAttendance->user_id,
                        'work_date'  => $targetAttendance->work_date,
                        'clock_in'  => $targetAttendance->clock_in,
                        'clock_out' => $targetAttendance->clock_out,
                        'created_by' => $targetAttendance->created_by,
                    ]);

        if ($targetAttendance->breakTimes) {
            $this->createBreakTimeHistory($targetAttendance, $attendanceHistory);
        }

        return $attendanceHistory;
    }

    private function createBreakTimeHistory($targetAttendance, $attendanceHistory)
    {
        foreach ($targetAttendance->breakTimes as $breakTime) {
            $this->attendanceRepository->createBreakTimeHistory([
                                        'attendance_history_id' => $attendanceHistory->id,
                                        'clock_in'  => $breakTime->clock_in,
                                        'clock_out' => $breakTime->clock_out,
                                        'created_by' => Auth::id(),
                                        'created_at' => $breakTime->created_at,
                                    ]);
        }
    }

    public function approveAttendanceApplication($applicationAttendanceId): bool
    {
        $attendanceApplication = AttendanceApplication::find($applicationAttendanceId);
        // 承認する内容を保存
        $applicationHistory = $this->createHistory($attendanceApplication->attendance);
        $attendanceApplication->approved_by = Auth::id();
        $attendanceApplication->approved_at = now();
        $attendanceApplication->attendance_history_id = $applicationHistory->id;
        return $this->attendanceRepository->approveAttendanceApplication($attendanceApplication);
    }

    public function attendanceRegister($action, $attendanceId)
    {
        return match ($action) {
            attendanceStatus::ON_DUTY->value => $this->startWork(),
            attendanceStatus::OFF->value => $this->endWork($attendanceId),
            attendanceStatus::ON_BREAK->value => $this->startBreak($attendanceId),
            attendanceStatus::OFF_BREAK->value => $this->endBreak($attendanceId),
        };
    }
    public function startWork()
    {
        return $this->attendanceRepository->createAttendance([
            'user_id' => Auth::id(),
            'work_date' => now()->format('Y-m-d'),
            'clock_in' => now(),
            'created_by' => Auth::id(),
        ]);
    }
    public function endWork($attendanceId)
    {
        $targetAttendance = Attendance::find($attendanceId);
        $targetAttendance->clock_out = now();
        return $this->attendanceRepository->updateAttendance($targetAttendance);
    }
    public function startBreak($attendanceId)
    {
        return $this->attendanceRepository->createBreakTime([
                            'attendance_id' => $attendanceId,
                            'clock_in'  => now(),
                            'created_by' => Auth::id(),
                        ]);
    }
    public function endBreak($attendanceId)
    {
        /** @var \App\Models\BreakTime $targetBreakTime */
        $targetBreakTime = BreakTime::where('attendance_id', $attendanceId)->whereNull('clock_out')->latest('clock_in')->first();
        if (is_null($targetBreakTime)) {
            return;
        }
        return $this->attendanceRepository->updateBreakTime(
            ['id' => $targetBreakTime->id],
            [
                'clock_out' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
