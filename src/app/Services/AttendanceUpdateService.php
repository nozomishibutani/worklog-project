<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use Carbon\Carbon;
use App\Models\AttendanceApplication;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use App\Enums\ApprovalStatus;
use Illuminate\Support\Facades\DB;
use App\Models\User;

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
        // H:iをDB登録用にフォーマット
        $workDay = [
                    'year' => $attendance['year'],
                    'month' => $attendance['month'],
                    'day' => $attendance['day'],
                ];

        $formatCarbonDate['work_in'] =  $attendance['work_in'] ? $this->attendanceFormatterService->formatCarbonDate($workDay, $attendance['work_in']) : null;
        $formatCarbonDate['work_out'] = $attendance['work_out'] ? $this->attendanceFormatterService->formatCarbonDate($workDay, $attendance['work_out']) : null;
        foreach ($attendance['break_in'] as $breakId => $breakTime) {
            $formatCarbonDate['break_in'][$breakId] = $breakTime ? $this->attendanceFormatterService->formatCarbonDate($workDay, $breakTime) : null;
            $formatCarbonDate['break_out'][$breakId]
                = $attendance['break_out'][$breakId] ? $this->attendanceFormatterService->formatCarbonDate($workDay, $attendance['break_out'][$breakId]) : null;
        }

        $targetAttendance = Attendance::find($attendance['attendance_id']);

        return DB::transaction(function () use ($targetAttendance, $attendance, $formatCarbonDate) {

            try {
                // 勤怠日
                if (is_null($targetAttendance)) {
                    // 新規登録
                    $this->attendanceRepository->createAttendance([
                        'user_id'      => $attendance['user_id'],
                        'work_date'    => $formatCarbonDate['work_date'],
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

                // 休憩
                foreach ($attendance['break_in'] as $id => $breakIn) {
                    if ($id !== 0 && is_null($breakIn) && is_null($attendance['break_out'][$id])) {
                        // 削除
                        $this->attendanceRepository->deleteBreakTime($id);
                    } elseif (!is_null($breakIn)) {
                        return match ($id) {
                            // 新規
                            0 => $this->attendanceRepository->createBreakTime([
                                'attendance_id' => $targetAttendance->id,
                                'clock_in'  => $formatCarbonDate['break_in'][$id],
                                'clock_out' => $formatCarbonDate['break_out'][$id],
                                'created_by' => Auth::id(),
                            ]),
                            // 更新
                            default => $this->attendanceRepository->updateBreakTime(
                                ['id' => $id,  'attendance_id' => $targetAttendance->id],
                                [
                                    'clock_in'  => $formatCarbonDate['break_in'][$id],
                                    'clock_out' => $formatCarbonDate['break_out'][$id],
                                    'updated_at' => now(),
                                ]
                            ),
                        };
                    }
                }
                // 修正リクエスト作成
                /** @var \App\Models\User $user */
                $user = Auth::user();
                $isAdmin = $user->isAdmin();
                return $this->attendanceRepository->createAttendanceApplication([
                        'attendance_id' => $targetAttendance->id,
                        'applied_by'    => Auth::id(),
                        'applied_at'    => now(),
                        'approved_by'  => $isAdmin ? Auth::id() : null,
                        'approved_at'  => $isAdmin ? now() : null,
                        'note'         => $attendance['note'],
                        'status'       => $isAdmin ? ApprovalStatus::APPROVED : ApprovalStatus::PENDING,
                    ]);
            } catch (\Throwable $e) {
                // ログ or エラーハンドリング
                throw $e;
            }
        });
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
