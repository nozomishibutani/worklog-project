<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakTimeApproval extends Model
{
    public $timestamps = false;

    protected $fillable = [
    'attendance_approval_id',
    'clock_in',
    'clock_out',
    ];

    protected $casts = [
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(AttendanceApproval::class);
    }
}
