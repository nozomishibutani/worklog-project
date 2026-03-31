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

    // DBから取った値を Carbon に変換する
    protected $casts = [
    'work_date' => 'datetime',
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function attendanceRequests()
    {
        return $this->hasOne(AttendanceRequest::class);
    }
}
