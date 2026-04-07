<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'created_by',
    ];

    protected $casts = [
    'work_date' => 'datetime',
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function attendanceApplications()
    {
        return $this->hasMany(AttendanceApplication::class);
    }

    public function latestAttendanceApplication()
    {
        return $this->hasOne(AttendanceApplication::class)->latestOfMany();
    }
}
