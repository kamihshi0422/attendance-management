<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;

class StatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤務外の場合勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee('勤務外');
    }

    /** @test */
    public function 出勤中の場合勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        Attendance::factory()->working()->create(['user_id' => $user->id]);

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩中の場合勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        Attendance::factory()->onBreak()->create(['user_id' => $user->id]);

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertSee('休憩中');
    }

    /** @test */
    public function 退勤済の場合勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create();
        Attendance::factory()->finished()->create(['user_id' => $user->id]);

        $this->actingAs($user);
        $response = $this->get('/attendance');

        $response->assertSee('退勤済');
    }
}
