<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'work_date', 'clock_in', 'clock_out', 'reason', 'status',
    ];

    // DBから取得した値を、自動で Carbon オブジェクトに変換するための設定
    protected $casts = [
        'work_date' => 'date',
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
    ];

    // 勤怠の所有者
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 勤怠に紐づく休憩情報
    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class); // 休憩は0件もあり
    }

    // 一般ユーザーが行った修正申請（1勤怠1申請の前提で hasOne）
    public function application()
    {
        return $this->hasOne(Application::class);
    }

    /**
     * 承認待ちの修正申請があるかどうか判定
     */
    public function hasPendingApplication(): bool
    {
        // hasOneの場合は単数
        return $this->application && $this->application->status === '承認待ち';
    }
}
