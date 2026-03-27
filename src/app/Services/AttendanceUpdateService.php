<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use Illuminate\Support\Facades\Log;

class AttendanceUpdateService
{
    protected AttendanceRepository $attendanceRepository;

    public function __construct(AttendanceRepository $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    public function updateAttendance(array $attendance): bool|\Illuminate\Http\RedirectResponse
    {

        try {
            if (!$attendance) {
                throw new \Exception();
            }

            return $this->attendanceRepository->updateAttendance($attendance);

        } catch (\Exception $e) {
            Log::error($e);
            return redirect()
                ->route('admin.index')
                ->with('alert', 'システムエラーが発生しました')
                ->with('alert-type', 'alert-error');
        }

    }

}
