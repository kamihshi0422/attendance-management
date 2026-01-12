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
        $admin = User::factory()->admin()->create();

        User::factory()->create([
            'name'  => '一般太郎',
            'email' => 'user@test.com',
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('一般太郎');
        $response->assertSee('user@test.com');
    }

    /** @test */
    public function ユーザーの勤怠情報が正しく表示される()
    {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->create();

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/attendance/{$user->id}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function 前月を押下した時に表示月の前月の情報が表示される()
    {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->create();

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::now()->subMonth()->startOfMonth(),
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/attendance/{$user->id}?month=" . Carbon::now()->subMonth()->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->subMonth()->format('Y年m月'));
    }

    /** @test */
    public function 翌月を押下した時に表示月の翌月の情報が表示される()
    {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->create();

        Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::now()->addMonth()->startOfMonth(),
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/attendance/{$user->id}?month=" . Carbon::now()->addMonth()->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->addMonth()->format('Y年m月'));
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $admin = User::factory()->admin()->create();
        $user  = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today(),
        ]);

        $this->actingAs($admin);

        $response = $this->get("/admin/attendance/detail/{$attendance->work_date}");

        $response->assertStatus(200);
        $response->assertSee($attendance->work_date->format('Y-m-d'));
    }
}
