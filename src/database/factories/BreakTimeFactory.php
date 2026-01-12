<?php

namespace Database\Factories;

use App\Models\BreakTime;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition()
    {
        $start = Carbon::today()->setTime(12, 0);

        return [
            'attendance_id' => Attendance::factory(),
            'break_start'   => $start,
            'break_end'     => $start->copy()->addMinutes(60),
        ];
    }
}
