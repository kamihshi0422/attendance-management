<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'attendance_id', 'corrected_clock_in',
        'corrected_clock_out',  'reason', 'status'
    ];

    protected $casts = [
        'corrected_clock_in'  => 'datetime',
        'corrected_clock_out' => 'datetime',
    ];

    // 修正申請の提出者
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 修正対象の勤怠（1勤怠1申請前提）
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

     // ←★ 追加：application_breaks のリレーション
    public function applicationBreaks()
    {
        return $this->hasMany(ApplicationBreak::class);
    }
}
