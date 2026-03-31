<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use Carbon\Carbon;
use App\Models\AttendanceRequest;
use App\Models\Attendance;

class AttendanceUpdateService
{
    protected AttendanceRepository $attendanceRepository;
    protected AttendanceFormatterService $attendanceFormatterService;

    public function __construct(AttendanceFormatterService $attendanceFormatterService, AttendanceRepository $attendanceRepository)
    {
        $this->attendanceFormatterService = $attendanceFormatterService;
        $this->attendanceRepository = $attendanceRepository;
    }

    public function updateAttendance(array $attendance): AttendanceRequest|\Illuminate\Http\RedirectResponse
    {
        //try {
            if (!$attendance) {
                throw new \Exception();
            }

            $targetAttendance = Attendance::find($attendance['attendance_id']);

            // 日付をDBフォーマットに合わせる
            $formatCarbonDate = [
                'work_in' => $attendance['work_in'],
                'work_out' => $attendance['work_out'],
                'break_in' => $attendance['break_in'],
                'break_out' => $attendance['break_out'],
            ];
            $workDay = [
                'year' => $attendance['year'],
                'month' => $attendance['month'],
                'day' => $attendance['day'],
            ];

            foreach ($formatCarbonDate as $key => $val) {
                if ($key === 'break_in' || $key === 'break_out') {
                    foreach ($val as $breakId => $breakTime) {
                        if (!is_null($breakTime)) {
                            $formatCarbonDate[$key][$breakId] = $this->attendanceFormatterService->formatCarbonDate($workDay, $attendance[$key][$breakId]);
                        } else {
                            $formatCarbonDate[$key][$breakId] = null;
                        }
                    }
                } elseif (!is_null($val)) {
                    $formatCarbonDate[$key] = $this->attendanceFormatterService->formatCarbonDate($workDay, $attendance[$key]);
                } else {
                    $formatCarbonDate[$key] = null;
                }
            }
            $formatCarbonDate['work_date'] = $this->attendanceFormatterService->formatCarbonDate($workDay);

            return $this->attendanceRepository->updateAttendance($attendance, $formatCarbonDate, $targetAttendance);
        // } catch (\Exception $e) {
        //     Log::error($e);
        //     return redirect()
        //         ->route('admin.index')
        //         ->with('alert', 'システムエラーが発生しました')
        //         ->with('alert-type', 'alert-error');
        // }
    }
}
