<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BreakTime extends Model
{
    use SoftDeletes;

    protected $fillable = [
    'attendance_id',
    'clock_in',
    'clock_out',
    'deleted_at'
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
