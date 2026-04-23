<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceChange extends Model
{
    /** @use HasFactory<AttendanceChange> */
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'work_date',
        'clock_in',
        'clock_out',
        'note',
        'applied_by',
        'applied_at',
    ];

    protected $casts = [
    'work_date' => 'datetime',
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    'applied_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function attendanceApproval()
    {
        return $this->HasOne(AttendanceApproval::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTimeChange::class);
    }

    public function latestAttendanceChange()
    {
        return $this->belongsTo(Attendance::class);

    }
}
