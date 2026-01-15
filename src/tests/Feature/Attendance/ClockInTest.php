<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 出勤ボタンが正しく機能する()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2026, 1, 11, 9, 0));

        $response = $this->post('/attendance/clock-in');

        $response->assertRedirect(url('/attendance'));

        $this->assertDatabaseHas('attendances', [
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => '出勤中',
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 出勤は一日一回のみできる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2026, 1, 11, 9, 0));

        $this->post('/attendance/clock-in');

        $this->post('/attendance/clock-in');

        $this->assertEquals(
            1,
            Attendance::where('user_id', $user->id)
                    ->where('work_date', Carbon::today()->toDateString())
                    ->count()
        );

        $response = $this->get('/attendance');
        $response->assertDontSee('<button type="submit" class="btn btn-clock-in">出勤</button>', false);
    }


    /** @test */
    public function 出勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2026, 1, 11, 9, 0));

        $this->post('/attendance/clock-in');

        $response = $this->get('/attendance/list');
        $response->assertSee('2026-01-11');
        $response->assertSee('09:00');
    }
}
