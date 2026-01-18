<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        foreach ($users as $user) {
            $daysAgoList = [1, 2, 3, 4, 5];

            foreach ($daysAgoList as $daysAgo) {
                $date = Carbon::today()->subDays($daysAgo);

                $clockIn  = $date->copy()->setTime(9, 0);
                $clockOut = $date->copy()->setTime(18, 0);

                Attendance::create([
                    'user_id'   => $user->id,
                    'work_date' => $date,
                    'clock_in'  => $clockIn,
                    'clock_out' => $clockOut,
                    'status'    => '退勤済',
                ]);
            }
        }
    }
}
