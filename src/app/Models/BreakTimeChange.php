<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BreakTimeChange extends Model
{
    /** @use HasFactory<BreakTimeChangeFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
    'attendance_change_id',
    'clock_in',
    'clock_out',
    ];

    protected $casts = [
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(AttendanceChange::class);
    }
}
