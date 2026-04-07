<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\SoftDeletes;

class BreakTimeHistory extends Model
{
    //use SoftDeletes;

    protected $fillable = [
    'attendance_history_id',
    'clock_in',
    'clock_out',
    'created_by',
    ];

    protected $casts = [
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    ];

    public function attendanceHistory()
    {
        return $this->belongsTo(AttendanceHistory::class);
    }
}
