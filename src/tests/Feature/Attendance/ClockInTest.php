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

        // 出勤処理
        $response = $this->post('/attendance/clock-in');

        // リダイレクト先を正確に比較
        $response->assertRedirect(url('/attendance'));

        // DBに出勤記録がある
        $this->assertDatabaseHas('attendances', [
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'status'    => '出勤中',
        ]);

        // 出勤後、ステータスが画面に表示される
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }
    /** @test */
    public function 出勤は一日一回のみできる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2026, 1, 11, 9, 0));

        // 1回目の出勤
        $this->post('/attendance/clock-in');

        // 2回目の出勤をPOSTしてもDBには追加されない
        $this->post('/attendance/clock-in');

        $this->assertEquals(
            1,
            Attendance::where('user_id', $user->id)
                      ->where('work_date', Carbon::today()->toDateString())
                      ->count()
        );
    }


    /** @test */
    public function 出勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Carbonで時刻固定
        Carbon::setTestNow(Carbon::create(2026, 1, 11, 9, 0));

        // 出勤処理
        $this->post('/attendance/clock-in');

        // 勤怠一覧画面で出勤時刻を確認
        $response = $this->get('/attendance/list');
        $response->assertSee('09:00');
    }
}

