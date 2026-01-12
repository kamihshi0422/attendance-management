<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'work_date' => Carbon::today(),
        ]);

        $response = $this->get("/admin/attendance/detail/{$attendance->work_date}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create();

        $response = $this->post("/admin/attendance/update/{$attendance->id}", [
            'clock_in'  => '18:00',
            'clock_out' => '09:00',
            'remark'    => '修正',
        ]);

        $response->assertSessionHasErrors('clock_in');
        $response->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create();

        $response = $this->post("/admin/attendance/update/{$attendance->id}", [
            'clock_in'    => '09:00',
            'clock_out'   => '18:00',
            'break_start' => '19:00',
            'break_end'   => '19:30',
            'remark'      => '修正',
        ]);

        $response->assertSessionHasErrors('break_start');
        $response->assertSee('休憩時間が不適切な値です');
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create();

        $response = $this->post("/admin/attendance/update/{$attendance->id}", [
            'clock_in'    => '09:00',
            'clock_out'   => '18:00',
            'break_start' => '17:00',
            'break_end'   => '19:00',
            'remark'      => '修正',
        ]);

        $response->assertSessionHasErrors('break_end');
        $response->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    /** @test */
    public function 備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create();

        $response = $this->post("/admin/attendance/update/{$attendance->id}", [
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
            'remark'    => '',
        ]);

        $response->assertSessionHasErrors('remark');
        $response->assertSee('備考を記入してください');
    }
}
