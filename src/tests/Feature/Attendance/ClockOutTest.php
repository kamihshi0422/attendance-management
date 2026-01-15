<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 退勤ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/attendance/clock-in');

        $response = $this->get('/attendance');
        $response->assertSee('退勤');

        $this->post('/attendance/clock-out');

        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    /** @test */
    public function 退勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->post('/attendance/clock-in');
        $this->post('/attendance/clock-out');

        $response = $this->get('/attendance/list');

        $expected = $now->format('H:i');
        $response->assertSeeText($expected);

        Carbon::setTestNow();
    }
}
