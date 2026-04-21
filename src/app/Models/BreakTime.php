<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    /** @use HasFactory<BreakTimeFactory> */
    use HasFactory;

    protected $fillable = [
    'attendance_id',
    'clock_in',
    'clock_out',
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
