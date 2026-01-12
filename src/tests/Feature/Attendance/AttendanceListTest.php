<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 自分の勤怠情報が全て表示されている()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 今日の勤怠情報を作成
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
        ]);

        $response = $this->get('/attendance/list');

        // 日付を 06/01(木) 形式で取得
        $today = Carbon::today();
        $weekday = ['日','月','火','水','木','金','土'][$today->dayOfWeek];
        $expected = $today->format('m/d') . "({$weekday})";

        $response->assertSee($expected);
    }

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        // 現在の年月が "YYYY/mm" 形式で表示されること
        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    /** @test */
    public function 前月ボタンを押すと前月が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 今月を基準に前月を計算（controller 内で subMonth() が呼ばれるのと同じ）
        $previousMonth = Carbon::now()->subMonth();

        // 前月リンクにアクセス（aタグ押下を擬似的に再現）
        $response = $this->get(route('attendanceList.show', [
            'year' => $previousMonth->year,
            'month' => $previousMonth->month,
        ]));

        // Blade で current-date が前月になっていることを確認
        $response->assertSee('<div class="current-date">'.$previousMonth->format('Y/m').'</div>', false);
    }

    /** @test */
    public function 翌月ボタンを押すと翌月が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $nextMonth = Carbon::now()->addMonth();

        // 翌月リンクにアクセス
        $response = $this->get(route('attendanceList.show', [
            'year' => $nextMonth->year,
            'month' => $nextMonth->month,
        ]));

        // Blade で current-date が翌月になっていることを確認
        $response->assertSee('<div class="current-date">'.$nextMonth->format('Y/m').'</div>', false);
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 今日の勤怠情報を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
        ]);

        // 勤怠一覧ページを取得
        $response = $this->get('/attendance/list');

        // 詳細ページURLを取得
        $detailUrl = route('attendanceDetail.show', [
            'id' => $attendance->id,
            'date' => $attendance->work_date->format('Y-m-d')
        ]);

        // 詳細ページにアクセス
        $response = $this->get($detailUrl);

        // ステータス 200 とページ内に「勤怠詳細」と日付が表示されていることを確認
        $response->assertStatus(200)->assertSee('勤怠詳細');
    }
}
