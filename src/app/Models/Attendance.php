<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
    //'work_date' => 'datetime',
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

    public function attendanceChanges()
    {
        return $this->hasMany(AttendanceChange::class);
    }

    public function latestAttendanceChange()
    {
        return $this->hasOne(AttendanceChange::class)->latestOfMany();
    }
}
