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

        // 出勤
        $this->post('/attendance/clock-in');

        // 画面に「退勤」ボタンが表示されていることを確認
        $response = $this->get('/attendance');
        $response->assertSee('退勤');

        // 退勤処理
        $this->post('/attendance/clock-out');

        // 期待挙動：ステータスが「退勤済」に変わっている
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    /** @test */
    public function 退勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // テスト用に時間を固定
        $now = Carbon::now();
        Carbon::setTestNow($now);

        // 出勤・退勤
        $this->post('/attendance/clock-in');
        $this->post('/attendance/clock-out');

        // 勤怠一覧画面取得
        $response = $this->get('/attendance/list');

        // 期待挙動：退勤時刻が勤怠一覧に表示されている
        $expected = $now->format('H:i');
        $response->assertSeeText($expected);

        // テスト終了後に時間のモックを解除
        Carbon::setTestNow();
    }
}
