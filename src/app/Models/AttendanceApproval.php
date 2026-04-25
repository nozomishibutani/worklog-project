<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceApproval extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'attendance_change_id',
        'work_date',
        'clock_in',
        'clock_out',
        'note',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceChange()
    {
        return $this->belongsTo(AttendanceChange::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTimeApproval::class);
    }
}
