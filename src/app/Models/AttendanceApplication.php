<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Enums\ApprovalStatus;

class AttendanceApplication extends Model
{
    protected $fillable = [
        'attendance_id',
        'applied_by',
        'applied_at',
        'approved_by',
        'approved_at',
        'status',
        'note',
    ];

    protected $casts = [
    'applied_at' => 'datetime',
    'corrected_at' => 'datetime',
    'approved_at' => 'datetime',
    'status' => ApprovalStatus::class,
    ];

    public function isApproved(): bool
    {
        return $this->status === ApprovalStatus::APPROVED;
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function latestAttendanceApplication()
    {
        return $this->belongsTo(Attendance::class);
    }
}
