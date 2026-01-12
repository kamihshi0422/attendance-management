<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        $date = Carbon::today();

        return [
            'user_id'   => User::factory(),
            'work_date' => $date->toDateString(),
            'clock_in'  => $date->copy()->setTime(9, 0),
            'clock_out' => $date->copy()->setTime(18, 0),
            'status'    => '退勤済',
        ];
    }

    public function working()
    {
        return $this->state(fn () => [
            'status' => '出勤中',
            'clock_out' => null,
        ]);
    }

    public function onBreak()
    {
        return $this->state(fn () => [
            'status' => '休憩中',
            'clock_out' => null,
        ]);
    }

    public function finished()
    {
        return $this->state(fn () => [
            'status' => '退勤済',
        ]);
    }
}