<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTimeSeeder extends Seeder
{
    public function run()
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            $start = Carbon::parse($attendance->clock_in)->addHours(3);
            $end   = $start->copy()->addHour();

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start'   => $start,
                'break_end'     => $end,
            ]);
        }
    }
}
