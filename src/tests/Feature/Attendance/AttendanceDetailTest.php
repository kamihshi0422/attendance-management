<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Attendance $attendance;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'テスト太郎']);
        $this->actingAs($this->user);

        $this->attendance = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::today(),
            'clock_in' => Carbon::today()->setTime(9, 0),
            'clock_out' => Carbon::today()->setTime(18, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $this->attendance->id,
            'break_start' => Carbon::today()->setTime(12, 0),
            'break_end' => Carbon::today()->setTime(13, 0),
        ]);
    }

    /** @test */
    public function 勤怠詳細画面の名前がログインユーザーの氏名になっている()
    {
        $response = $this->get("/attendance/list/detail/{$this->attendance->id}");

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
    }

    /** @test */
    public function 勤怠詳細画面の日付が選択した日付になっている()
    {
        $response = $this->get("/attendance/list/detail/{$this->attendance->id}");

        $response->assertStatus(200);
        $response->assertSee($this->attendance->work_date->format('Y年'));
        $response->assertSee($this->attendance->work_date->format('n月j日'));
    }

    /** @test */
    public function 出勤退勤にて記されている時間がログインユーザーの打刻と一致している()
    {
        $response = $this->get("/attendance/list/detail/{$this->attendance->id}");

        $response->assertStatus(200);
        $response->assertSee($this->attendance->clock_in->format('H:i'));
        $response->assertSee($this->attendance->clock_out->format('H:i'));
    }

    /** @test */
    public function 休憩にて記されている時間がログインユーザーの打刻と一致している()
    {
        $response = $this->get("/attendance/list/detail/{$this->attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
