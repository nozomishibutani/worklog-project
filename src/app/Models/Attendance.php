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
        'corrected_by',
        'approved_by',
        'approved_at',
        'note',
        'status',
    ];

    // DBから取った値を Carbon に変換する
    protected $casts = [
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

    public function breaksTime()
    {
        return $this->hasMany(Attendance::class);
    }
}
