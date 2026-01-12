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

        // 出勤
        $this->post('/attendance/clock-in');
        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        // 休憩開始
        $this->post('/attendance/break-start');

        // 期待挙動：ステータスが「休憩中」になっている
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    /** @test */
    public function 休憩は一日に何回でもできる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->post('/attendance/clock-in');

        // 1回目の休憩
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        // 期待挙動：画面上に「休憩入」ボタンが表示されている
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

        // 期待挙動：ステータスが「出勤中」に戻っている
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩戻は一日に何回でもできる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->post('/attendance/clock-in');

        // 1回目
        $this->post('/attendance/break-start');
        $this->post('/attendance/break-end');

        // 2回目
        $this->post('/attendance/break-start');

        // 期待挙動：画面上に「休憩戻」ボタンが表示される
        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    /** @test */
    public function 休憩時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 現在時刻を固定
        $startTime = \Carbon\Carbon::now();
        \Carbon\Carbon::setTestNow($startTime);

        // 出勤
        $this->post('/attendance/clock-in');

        // 休憩開始
        $this->post('/attendance/break-start');

        // 休憩終了 → 10分後に設定
        $endTime = $startTime->copy()->addMinutes(10);
        \Carbon\Carbon::setTestNow($endTime);
        $this->post('/attendance/break-end');

        // 退勤も登録
        $this->post('/attendance/clock-out');

        // 勤怠一覧画面取得
        $response = $this->get('/attendance/list');

        // 最新休憩レコード取得
        $break = \DB::table('break_times')->latest('id')->first();

        $breakMinutes = \Carbon\Carbon::parse($break->break_end)
                        ->diffInMinutes(\Carbon\Carbon::parse($break->break_start));

        $hours = intdiv($breakMinutes, 60);
        $minutes = $breakMinutes % 60;
        $expected = sprintf('%d:%02d', $hours, $minutes);

        $response->assertSeeText($expected);

        // テスト終了後に時間のモック解除
        \Carbon\Carbon::setTestNow();
    }
}
