<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    protected $fillable = [
    'user_id',
    'attendance_id',
    'clock_in',
    'clock_out',
    'created_by',
    'corrected_by',
    ];

    // DBから取った値を Carbon に変換する
    protected $casts = [
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
