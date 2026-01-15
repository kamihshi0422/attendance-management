<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition()
    {
        return [
            'user_id'       => User::factory(),
            'attendance_id' => Attendance::factory(),
            'corrected_clock_in'  => now()->setTime(10, 0),
            'corrected_clock_out' => now()->setTime(19, 0),
            'reason'        => '修正理由',
            'status'        => '修正前', // ★ default
        ];
    }

    public function pending()
    {
        return $this->state(fn () => [
            'status' => '承認待ち',
        ]);
    }

    public function approved()
    {
        return $this->state(fn () => [
            'status' => '承認済み',
        ]);
    }
}
