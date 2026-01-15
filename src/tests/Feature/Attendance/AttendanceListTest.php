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

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
        ]);

        $response = $this->get('/attendance/list');

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

        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    /** @test */
    public function 前月ボタンを押すと前月が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $previousMonth = Carbon::now()->subMonth();

        $response = $this->get(route('attendanceList.show', [
            'year' => $previousMonth->year,
            'month' => $previousMonth->month,
        ]));

        $response->assertSee('<div class="current-date">'.$previousMonth->format('Y/m').'</div>', false);
    }

    /** @test */
    public function 翌月ボタンを押すと翌月が表示される()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $nextMonth = Carbon::now()->addMonth();

        $response = $this->get(route('attendanceList.show', [
            'year' => $nextMonth->year,
            'month' => $nextMonth->month,
        ]));

        $response->assertSee('<div class="current-date">'.$nextMonth->format('Y/m').'</div>', false);
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
        ]);

        $response = $this->get('/attendance/list');

        $detailUrl = route('attendanceDetail.show', [
            'id' => $attendance->id,
            'date' => $attendance->work_date->format('Y-m-d')
        ]);

        $response = $this->get($detailUrl);

        $response->assertStatus(200)->assertSee('勤怠詳細');
    }
}
