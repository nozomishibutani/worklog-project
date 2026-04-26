<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use App\Services\AttendanceResolverService;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AttendanceChange;

class AttendanceUpdateService
{
    protected AttendanceRepository $attendanceRepository;
    protected AttendanceFormatterService $attendanceFormatterService;
    protected AttendanceResolverService $attendanceResolverService;

    public function __construct(
        AttendanceFormatterService $attendanceFormatterService,
        AttendanceRepository $attendanceRepository,
        AttendanceResolverService $attendanceResolverService,
    ) {
        $this->attendanceFormatterService = $attendanceFormatterService;
        $this->attendanceRepository = $attendanceRepository;
        $this->attendanceResolverService =  $attendanceResolverService;
    }

    public function applyAttendance($applyAttendance): ?AttendanceChange
    {
        $targetAttendance = Attendance::find($applyAttendance['attendance_id']);
        $formatCarbonDate =  $this->attendanceFormatterService->buildWorkDateFromEditAttendance($applyAttendance);

        return DB::transaction(function () use ($targetAttendance, $applyAttendance, $formatCarbonDate) {
            try {
                // 修正登録
                if (is_null($targetAttendance)) {
                    // 打刻ない場合は空レコード作成
                    $createAttendance = $this->createAttendance($applyAttendance, $formatCarbonDate);
                    $applyAttendance['attendance_id'] = $createAttendance->id;
                }
                $createAttendanceChange = $this->createAttendanceChange($applyAttendance, $formatCarbonDate);
                // 休憩
                if (isset($applyAttendance['break_in'])) {
                    $this->createBreakTimeChanges($createAttendanceChange, $applyAttendance, $formatCarbonDate);
                }
                return $createAttendanceChange;
            }  catch (\Exception $e) {
                Log::error($e);
                return null;
            }
        });
    }

    private function createAttendance($attendance, $formatCarbonDate): Attendance
    {
        // 新規登録
        return $this->attendanceRepository->createAttendance([
            'user_id'      => $attendance['user_id'],
            'work_date'    => $formatCarbonDate['work_date']->copy()->format('Y-m-d'),
            'clock_in'     => null,
            'clock_out'    => null,
        ]);
    }

    private function createAttendanceChange($applyAttendance, $formatCarbonDate): AttendanceChange
    {
        // 修正
        return $this->attendanceRepository->createAttendanceChange([
            'user_id'      => $applyAttendance['user_id'],
            'attendance_id' => $applyAttendance['attendance_id'],
            'work_date'    => $formatCarbonDate['work_date']->copy()->format('Y-m-d'),
            'clock_in'     => $formatCarbonDate['work_in'],
            'clock_out'    => $formatCarbonDate['work_out'],
            'note'          => $applyAttendance['note'],
            'applied_by' => Auth::id(),
            'applied_at' => now(),
        ]);
    }

    private function createBreakTimeChanges($createAttendanceChange, $attendance, $formatCarbonDate): void
    {
        foreach ($attendance['break_in'] as $id => $breakIn) {
            if (!is_null($breakIn)) {
                $this->attendanceRepository->createBreakTimeChanges([
                    'attendance_change_id' => $createAttendanceChange->id,
                    'clock_in'  => $formatCarbonDate['break_in'][$id],
                    'clock_out' => $formatCarbonDate['break_out'][$id],
                    'created_by' => Auth::id(),
                ]);
            }
        }
    }

    public function approveAttendance($attendanceChangeId, $approvedBy): bool
    {
        try {
            $attendanceChange = AttendanceChange::find($attendanceChangeId);
            return DB::transaction(function () use ($attendanceChange, $approvedBy) {
                // 承認する内容を履歴として保存
                $createApproval = $this->attendanceRepository->createAttendanceApproval([
                    'user_id' => $attendanceChange->user_id,
                    'attendance_change_id' => $attendanceChange->id,
                    'work_date' => $attendanceChange->work_date,
                    'clock_in' => $attendanceChange->clock_in,
                    'clock_out' => $attendanceChange->clock_out,
                    'approved_by' => $approvedBy,
                    'approved_at' => now(),
                    'note' => $attendanceChange->note,
                ]);
                if ($attendanceChange->breakTimes) {
                    foreach ($attendanceChange->breakTimes as $breakTime) {
                        $this->attendanceRepository->createBreakTimeApprovals([
                                                'attendance_approval_id' => $createApproval->id,
                                                'clock_in' => $breakTime->clock_in,
                                                'clock_out' => $breakTime->clock_out,
                                            ]);
                    }
                }
                return true;
            });
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    public function attendanceRegister($action, $attendanceId)
    {
        try {
            return match ($action) {
                attendanceStatus::ON_DUTY->value => $this->startWork(),
                attendanceStatus::OFF->value => $this->endWork($attendanceId),
                attendanceStatus::ON_BREAK->value => $this->startBreak($attendanceId),
                attendanceStatus::OFF_BREAK->value => $this->endBreak($attendanceId),
            };
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    private function startWork()
    {
        return $this->attendanceRepository->createAttendance([
            'user_id' => Auth::id(),
            'work_date' => now()->format('Y-m-d'),
            'clock_in' => now(),
        ]);
    }

    private function endWork($attendanceId)
    {
        $targetAttendance = Attendance::find($attendanceId);
        $targetAttendance->clock_out = now();
        return $this->attendanceRepository->updateAttendance($targetAttendance);
    }

    private function startBreak($attendanceId)
    {
        return $this->attendanceRepository->createBreakTime([
                            'attendance_id' => $attendanceId,
                            'clock_in'  => now(),
                        ]);
    }

    private function endBreak($attendanceId)
    {
        /** @var \App\Models\BreakTime $targetBreakTime */
        $targetBreakTime = BreakTime::where('attendance_id', $attendanceId)->whereNull('clock_out')->latest('clock_in')->first();
        if (is_null($targetBreakTime)) {
            throw new \Exception();
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
