<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceHistory extends Model
{
    protected $fillable = [
        'clock_in',
        'clock_out',
        'created_by',
    ];

    protected $casts = [
    'clock_in' => 'datetime',
    'clock_out' => 'datetime',
    ];

    public function attendanceApplication()
    {
        return $this->HasOne(AttendanceApplication::class);
    }
    public function breakTimeHistories()
    {
        return $this->HasMany(AttendanceApplication::class);
    }
}
