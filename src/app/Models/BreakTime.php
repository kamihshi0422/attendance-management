<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'break_times';
    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end'
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
