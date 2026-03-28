<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Services\AttendanceFormatterService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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

    public function updateAttendance(array $attendance): Attendance|\Illuminate\Http\RedirectResponse
    {
        try {
            if (!$attendance) {
                throw new \Exception();
            }

            $targetAttendance = Attendance::find($attendance['attendance_id']);

            // 日付をDBフォーマットに合わせる
            $formatCarbonDate = [];
            $workDay = [
                'year' => $attendance['year'],
                'month' => $attendance['month'],
                'day' => $attendance['day'],
            ];
            $formatCarbonDate['work_day'] = $this->attendanceFormatterService->formatCarbonDate($workDay)->format('Y-m-d');
            $formatCarbonDate['work_in'] = $this->attendanceFormatterService->formatCarbonDate($workDay, $attendance['work_in']);
            $formatCarbonDate['work_out'] = $this->attendanceFormatterService->formatCarbonDate($workDay, $attendance['work_out']);
            foreach ($attendance['break_in'] as $id => $breakIn) {
                if (!is_null($attendance['break_in'][$id]) && !is_null($attendance['break_out'][$id])) {
                    $formatCarbonDate['break_in'][$id] = $this->attendanceFormatterService->formatCarbonDate($workDay, $attendance['break_in'][$id]);
                    $formatCarbonDate['break_out'][$id] = $this->attendanceFormatterService->formatCarbonDate($workDay, $attendance['break_out'][$id]);
                }
            }
            return $this->attendanceRepository->updateAttendance($attendance, $formatCarbonDate, $targetAttendance);
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()
                ->route('admin.index')
                ->with('alert', 'システムエラーが発生しました')
                ->with('alert-type', 'alert-error');
        }
    }
}
