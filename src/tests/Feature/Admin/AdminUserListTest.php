<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminUserListTest extends TestCase
{
    use RefreshDatabase;

 /** @test */
    public function 管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $users = User::factory()->count(3)->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('staffList.show'));

        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    /** @test */
    public function ユーザーの勤怠情報が正しく表示される()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'user',
        ]);

        Attendance::factory()->create([
            'user_id'   => $staff->id,
            'work_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('staffAttendance.show', $staff->id));

        $response->assertStatus(200);
        $response->assertSee($staff->name);
        $response->assertSee(Carbon::now()->format('m/d'));
    }

    /** @test */
    public function 前月を押下した時に表示月の前月の情報が表示される()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'user',
        ]);

        $lastMonth = Carbon::now()->subMonth();

        Attendance::factory()->create([
            'user_id'   => $staff->id,
            'work_date' => $lastMonth->copy()->startOfMonth()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(
            route('staffAttendance.show', [
                'id'    => $staff->id,
                'year'  => $lastMonth->year,
                'month' => $lastMonth->month,
            ])
        );

        $response->assertStatus(200);

        $response->assertSee($lastMonth->format('Y/m'));
    }

    /** @test */
    public function 翌月を押下した時に表示月の翌月の情報が表示される()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'user',
        ]);

        $nextMonth = Carbon::now()->addMonth();

        Attendance::factory()->create([
            'user_id'   => $staff->id,
            'work_date' => $nextMonth->copy()->startOfMonth()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(
            route('staffAttendance.show', [
                'id'    => $staff->id,
                'year'  => $nextMonth->year,
                'month' => $nextMonth->month,
            ])
        );

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $staff = User::factory()->create([
            'role' => 'user',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id'   => $staff->id,
            'work_date' => '2025-12-01',
        ]);

        $response = $this->actingAs($admin)->get(
            '/admin/attendance/' . $attendance->id
            . '?date=' . $attendance->work_date
            . '&user_id=' . $staff->id
        );

        $response->assertStatus(200);
        $response->assertSee('2025-12-01');
        $response->assertSee($staff->name);
    }
}
