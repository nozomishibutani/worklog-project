<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case OFF = 'off';
    case ON_DUTY  = 'on_duty';
    case ON_BREAK = 'on_break';
    case OFF_BREAK = 'off_break';
    case OFF_DUTY = 'off_duty';

    public function label(): string
    {
        return match($this) {
            self::OFF => '勤務外',
            self::ON_DUTY => '出勤中',
            self::ON_BREAK => '休憩中',
            self::OFF_DUTY => '退勤済',
        };
    }
}
