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
        $user  = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/attendance');

        $response->assertSee($user->name);
    }

    /** @test */
    public function 遷移した際に現在の日付が表示される()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->get('/admin/attendance');

        $response->assertSee(Carbon::today()->format('Y-m-d'));
    }

    /** @test */
    public function 前日を押下した時に前の日の勤怠情報が表示される()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $yesterday = Carbon::yesterday();

        Attendance::factory()->create([
            'work_date' => $yesterday,
        ]);

        $response = $this->get('/admin/attendance?date=' . $yesterday->format('Y-m-d'));

        $response->assertSee($yesterday->format('Y-m-d'));
    }

    /** @test */
    public function 翌日を押下した時に次の日の勤怠情報が表示される()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $tomorrow = Carbon::tomorrow();

        $response = $this->get('/admin/attendance?date=' . $tomorrow->format('Y-m-d'));

        $response->assertSee($tomorrow->format('Y-m-d'));
    }
}
