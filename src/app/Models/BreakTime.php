<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    protected $fillable = [
    'user_id',
    'attendance_id',
    'updated_by',
    'start_time',
    'end_time',
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
