<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceApplication;

class CommonController extends Controller
{
    public function applicationIndex(Request $request)
    {
        if (auth('admin')->check()) {
            $mode = $request->query('mode');
            switch ($mode) {
                case AttendanceStatus::PENDING->value:
                    $attendanceApplications = AttendanceApplication::with('attendance.user')->where('status', AttendanceStatus::PENDING->value)->get();
                    break;
                case AttendanceStatus::APPROVED->value:
                    $attendanceApplications = AttendanceApplication::with('attendance.user')
                                                                    ->where('status', AttendanceStatus::APPROVED->value)
                                                                    ->orderBy('approved_at', 'desc')
                                                                    ->get();
                    break;
                default:
                    $attendanceApplications = null;
                    break;
            }
            return view('admin/application_index', compact('attendanceApplications'));
        }

        if (auth('web')->check()) {
            //$mode = $request->query('mode');
            // switch ($mode) {
            //     case AttendanceStatus::PENDING->value:
            //         $attendanceApplications = AttendanceApplication::with('attendance.user')->where('status', AttendanceStatus::PENDING->value)->get();
            //         break;
            //     case AttendanceStatus::APPROVED->value:
            //         $attendanceApplications = AttendanceApplication::with('attendance.user')->where('status', AttendanceStatus::APPROVED->value)->get();
            //         break;
            //     default:
            //         $attendanceApplications = null;
            //         break;
            // }

            return view('welcome');

        }



    }
}
