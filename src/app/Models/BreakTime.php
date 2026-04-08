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
    'created_by',
    'deleted_at'
    ];

    protected $casts = [
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
