<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Carbon\Carbon;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 休憩ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/attendance/clock-in');
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        $this->post('/attendance/break-start');

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    /** @test */
    public function 休憩は一日に何回でもできる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->post('/attendance/clock-in');

        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        $response = $this->get('/attendance');
        $response->assertSee('休憩入');
    }

    /** @test */
    public function 休憩戻ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->post('/attendance/clock-in');

        $this->post('/attendance/break-start');
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
        $this->post('/attendance/break-end');

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩戻は一日に何回でもできる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->post('/attendance/clock-in');

        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        $this->post('/attendance/break-start');

        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /** @test */
    public function 休憩時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $startTime = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($startTime);

        $this->post('/attendance/clock-in');

        $this->post('/attendance/break-start');

        $endTime = $startTime->copy()->addMinutes(10);
        \Carbon\Carbon::setTestNow($endTime);
        $this->post('/attendance/break-end');

        $this->post('/attendance/clock-out');

        $response = $this->get('/attendance/list');

        $break = \DB::table('break_times')->latest('id')->first();

        $breakMinutes = \Carbon\Carbon::parse($break->break_end)
                        ->diffInMinutes(\Carbon\Carbon::parse($break->break_start));

        $hours = intdiv($breakMinutes, 60);
        $minutes = $breakMinutes % 60;
        $expected = sprintf('%d:%02d', $hours, $minutes);

        $response->assertSeeText($expected);

        \Carbon\Carbon::setTestNow();
    }
}
