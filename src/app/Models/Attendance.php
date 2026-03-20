<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'approved_by',
        'approved_at',
        'updated_by',
        'memo',
        'start_time',
        'end_time',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaksTime()
    {
        return $this->hasMany(Attendance::class);
    }
}
