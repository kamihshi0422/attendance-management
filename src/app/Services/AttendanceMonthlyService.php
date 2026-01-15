<?php

namespace App\Services;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceMonthlyService
{
    public function build(
        User $user,
        Carbon $month,
        AttendanceTimeService $timeService
    ): array {
        // 当月の勤怠取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('work_date', $month->year)
            ->whereMonth('work_date', $month->month)
            ->with('breakTimes')
            ->get()
            ->keyBy(fn ($attendance) => $attendance->work_date->toDateString());

        $days = [];

        for ($day = 1; $day <= $month->daysInMonth; $day++) {

            $date = Carbon::create($month->year, $month->month, $day);
            $dateKey = $date->toDateString();

            $attendance = $attendances->get($dateKey);

            $clockIn  = '';
            $clockOut = '';
            $break    = '';
            $total    = '';
            $recordId = null;

            if ($attendance) {
                $recordId = $attendance->id;

                $clockIn  = $attendance->clock_in?->format('H:i') ?? '';
                $clockOut = $attendance->clock_out?->format('H:i') ?? '';

                // ⭐ 時間計算は Service に完全委譲
                $times = $timeService->calculate($attendance);
                $break = $times['break_time'];
                $total = $times['work_time'];
            }

            $days[] = [
                'raw_date'  => $dateKey,
                'weekday'   => $date->translatedFormat('m/d(D)'),
                'record_id' => $recordId,
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
                'break'     => $break,
                'total'     => $total,
            ];
        }

        return $days;
    }
}
