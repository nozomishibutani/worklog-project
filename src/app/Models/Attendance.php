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
        'note',
        'clock_in',
        'clock_out',
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
