<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    // DBから取った値を Carbon に変換する
    protected $casts = [
    'corrected_at' => 'datetime',
    'approved_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
