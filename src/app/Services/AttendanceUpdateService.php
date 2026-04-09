<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use App\Services\AttendanceResolverService;
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
use App\Enums\Guard;
use App\Models\AttendanceChange;
use Database\Seeders\AttendanceHistorySeeder;

use function Symfony\Component\Clock\now;

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
    public function applyAttendance($attendance): AttendanceChange|\Illuminate\Http\RedirectResponse
    {
        $targetAttendance = Attendance::find($attendance['attendance_id']);
        $formatCarbonDate =  $this->attendanceFormatterService->buildWorkDateFromEditAttendance($attendance);

        return DB::transaction(function () use ($targetAttendance, $attendance, $formatCarbonDate) {
            //try {
            // 修正勤怠登録
            if (is_null($targetAttendance)) {
                // 打刻ない場合は空レコード作成
                $createAttendance = $this->createAttendance($attendance, $formatCarbonDate);
                $attendance['attendance_id'] = $createAttendance->id;
            }
            $createAttendanceChange = $this->createAttendanceChange($attendance, $formatCarbonDate);
            // 休憩
            if (isset($attendance['break_in'])) {
                $this->createBreakTimeChanges($createAttendanceChange, $attendance, $formatCarbonDate);
            }
            return $createAttendanceChange;
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

    private function createAttendanceChange($attendance, $formatCarbonDate): AttendanceChange
    {
        // 修正
        return $this->attendanceRepository->createAttendanceChange([
            'user_id'      => $attendance['user_id'],
            'attendance_id' => $attendance['attendance_id'],
            'work_date'    => $formatCarbonDate['work_in']->copy()->format('Y-m-d'),
            'clock_in'     => $formatCarbonDate['work_in'],
            'clock_out'    => $formatCarbonDate['work_out'],
            'note'          => $attendance['note'],
            'applied_by' => Auth::id(),
            'applied_at' => now(),
        ]);
    }

    public function createAttendance($attendance, $formatCarbonDate): Attendance
    {
        // 新規登録
        return $this->attendanceRepository->createAttendance([
            'user_id'      => $attendance['user_id'],
            'work_date'    => $formatCarbonDate['work_in']->copy()->format('Y-m-d'),
            'clock_in'     => null,
            'clock_out'    => null,
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

    private function createApplicationAttendance(Attendance $saveAttendance, array $attendance, $createHistory = null): AttendanceApplication
    {
        // 現在フラグをfalseにする
        $this->setIsCurrentFlag($saveAttendance);

        if (auth('admin')->check()) {
            // 管理画面から管理者が修正した場合、承認プロセスを省く
            //$attendanceHistory = $this->createHistory($saveAttendance);
            return $this->attendanceRepository->createAttendanceApplication([
                            'attendance_id' => $saveAttendance->id,
                            'attendance_history_id' => $createHistory?->id, //勤怠登録ない場合は履歴残さない
                            'applied_by'    => Auth::id(),
                            'applied_at'    => now(),
                            'approved_by'  => Auth::id(),
                            'approved_at'  => now(),
                            'note'         => $attendance['note'],
                            'is_current' => true,
                        ]);
        } else {
            //一般画面からユーザーが修正した
            return $this->attendanceRepository->createAttendanceApplication([
                            'attendance_id' => $saveAttendance->id,
                            'attendance_history_id' => $createHistory?->id, //勤怠登録ない場合は履歴残さない
                            'applied_by'    => Auth::id(),
                            'applied_at'    => now(),
                            'approved_by'  => null,
                            'approved_at'  => null,
                            'note'         => $attendance['note'],
                            'is_current' => true,
                        ]);
        }
    }

    private function setIsCurrentFlag(Attendance $saveAttendance): bool
    {
        $saveAttendance->latestAttendanceApplication->is_current = false;
        return $saveAttendance->save();
    }

    private function createHistory($targetAttendance): AttendanceHistory
    {
        $attendanceHistory = $this->attendanceRepository->createAttendanceHistory([
                        'user_id'  => $targetAttendance->user_id,
                        'work_date'  => $targetAttendance->work_date,
                        'clock_in'  => $targetAttendance->clock_in,
                        'clock_out' => $targetAttendance->clock_out,
                        'created_by' => $targetAttendance->created_by,
                        'created_at' => $targetAttendance->created_at,
                        'updated_at' => $targetAttendance->updated_at,
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
                                        'created_by' => $breakTime->created_by,
                                        'created_at' => $breakTime->created_at,
                                        'updated_at' => $breakTime->updated_at,
                                    ]);
        }
    }

    public function approveAttendanceApplication($applicationAttendanceId): bool
    {
        $attendanceApplication = AttendanceApplication::find($applicationAttendanceId);
        // 承認する内容を保存
        //$applicationHistory = $this->createHistory($attendanceApplication->attendance);
        $attendanceApplication->approved_by = Auth::id();
        $attendanceApplication->approved_at = now();
        //$attendanceApplication->attendance_history_id = $applicationHistory->id;
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
