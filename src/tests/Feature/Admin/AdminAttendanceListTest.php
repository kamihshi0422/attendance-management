<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function その日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        $admin = User::factory()->admin()->create();

        $users = User::factory()->count(2)->create();

        $today = Carbon::today();

        foreach ($users as $user) {
            Attendance::factory()->create([
                'user_id'   => $user->id,
                'work_date' => $today->toDateString(),
                'clock_in'  => Carbon::createFromTime(9, 0),
                'clock_out' => Carbon::createFromTime(18, 0),
            ]);
        }

        $response = $this->actingAs($admin)->get(
            route('admin.attendanceList.show', [
                'date' => $today->toDateString(),
            ])
        );

        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee('09:00');
            $response->assertSee('18:00');
        }
    }

    /** @test */
    public function 遷移した際に現在の日付が表示される()
    {
        $admin = User::factory()->admin()->create();
        $today = Carbon::today();

        $response = $this->actingAs($admin)->get(
            route('admin.attendanceList.show')
        );

        $response->assertStatus(200);

        $response->assertSee($today->format('Y年n月j日'));
        $response->assertSee($today->format('Y/m/d'));
    }

    /** @test */
    public function 前日を指定した時に前の日の勤怠情報が表示される()
    {
        $admin = User::factory()->admin()->create();
        $yesterday = Carbon::yesterday();

        Attendance::factory()->create([
            'work_date' => $yesterday->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.attendanceList.show', [
                'date' => $yesterday->toDateString(),
            ])
        );

        $response->assertStatus(200);
        $response->assertSee($yesterday->format('Y年n月j日'));
        $response->assertSee($yesterday->format('Y/m/d'));
    }

    /** @test */
    public function 翌日を指定した時に次の日の勤怠情報が表示される()
    {
        $admin = User::factory()->admin()->create();
        $tomorrow = Carbon::tomorrow();

        $response = $this->actingAs($admin)->get(
            route('admin.attendanceList.show', [
                'date' => $tomorrow->toDateString(),
            ])
        );

        $response->assertStatus(200);

        $response->assertSee($tomorrow->format('Y年n月j日'));
        $response->assertSee($tomorrow->format('Y/m/d'));
    }
}
