<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceTimeService
{
    public function calculate(Attendance $attendance): array
    {
        if (!$attendance->clock_in || !$attendance->clock_out) {
            return [
                'break_minutes' => 0,
                'work_minutes'  => 0,
                'break_time'    => '',
                'work_time'     => '',
            ];
        }

        $clockIn  = Carbon::parse($attendance->clock_in);
        $clockOut = Carbon::parse($attendance->clock_out);

        $breakMinutes = $this->calculateBreakMinutes($attendance);

        $workMinutes = max(
            0,
            $clockIn->diffInMinutes($clockOut) - $breakMinutes
        );

        return [
            'break_minutes' => $breakMinutes,
            'work_minutes'  => $workMinutes,
            'break_time'    => $this->formatMinutes($breakMinutes),
            'work_time'     => $this->formatMinutes($workMinutes),
        ];
    }

    private function calculateBreakMinutes(Attendance $attendance): int
    {
        return $attendance->breakTimes->sum(function ($break) {

            if (!$break->break_end) {
                return 0;
            }

            $start = Carbon::parse($break->break_start);
            $end   = Carbon::parse($break->break_end);

            return $start->diffInMinutes($end);
        });
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '';
        }

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        return $hours . ':' . str_pad($mins, 2, '0', STR_PAD_LEFT);
    }
}
