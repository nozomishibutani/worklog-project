<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceCalculatorService;
use App\Services\AttendanceUpdateService;
use App\Services\AttendanceFormatterService;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Auth;
use App\Enums\AttendanceStatus;

use function Symfony\Component\Clock\now;

class UserController extends Controller
{
    protected AttendanceCalculatorService $attendanceCalculatorService;
    protected AttendanceUpdateService $attendanceUpdateService;
    protected AttendanceFormatterService $attendanceFormatterService;

    public function __construct(
        AttendanceCalculatorService $attendanceCalculatorService,
        AttendanceUpdateService $attendanceUpdateService,
        AttendanceFormatterService $attendanceFormatterService,
    ) {
        $this->attendanceCalculatorService = $attendanceCalculatorService;
        $this->attendanceUpdateService = $attendanceUpdateService;
        $this->attendanceFormatterService = $attendanceFormatterService;
    }
    public function index()
    {
        [
            'attendanceStatus' => $attendanceStatus,
            'attendance' => $attendance,
        ] = $this->attendanceCalculatorService->getUserAttendanceStatus(now()->format('Y-m-d'));

        $time = now()->format('H:i');
        $day = $this->attendanceFormatterService->addDay(now(), 'Y年n月j日');

        return view('user.index', compact('attendanceStatus', 'attendance', 'time', 'day'));
    }

    public function register(Request $request)
    {
        $attendanceId = $request->input('attendance_id');
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            $result =  $this->attendanceUpdateService->attendanceRegister($action, $attendanceId);
            if ($result) {
                return redirect()->route('user.index');
            }
        }

        return redirect()->route('user.index')
                                        ->with('alert', 'システムエラーが発生しました')
                                        ->with('alert-type', 'alert-error');
    }
}
