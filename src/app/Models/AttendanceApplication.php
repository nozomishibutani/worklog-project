<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\ApprovalStatus;

class AttendanceApplication extends Model
{
    protected $fillable = [
        'attendance_id',
        'applied_by',
        'applied_at',
        'approved_by',
        'approved_at',
        'note',
    ];

    protected $casts = [
    'applied_at' => 'datetime',
    'corrected_at' => 'datetime',
    'approved_at' => 'datetime',
    ];

    public function isApproved(): bool
    {
        return $this->approved_by !== null && $this->approved_at !== null;
    }

    public function approvalStatus(): ApprovalStatus
    {
        return $this->isApproved()
            ? ApprovalStatus::APPROVED
            : ApprovalStatus::PENDING;
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
