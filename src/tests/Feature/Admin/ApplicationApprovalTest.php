<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Application;

class ApplicationApprovalTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 承認待ちの修正申請が全て表示されている()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Application::factory()->pending()->create();

        $response = $this->get('/admin/applications');

        $response->assertSee('承認待ち');
    }

    /** @test */
    public function 承認済みの修正申請が全て表示されている()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $application = Application::factory()->create([
            'status' => 'approved',
            'remark' => '承認済み申請',
        ]);

        $response = $this->get('/admin/applications?status=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済み申請');
    }

    /** @test */
    public function 修正申請の詳細内容が正しく表示されている()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
        ]);

        $application = Application::factory()->create([
            'attendance_id' => $attendance->id,
            'clock_in'      => '10:00',
            'clock_out'     => '19:00',
            'remark'        => '時間修正',
        ]);

        $response = $this->get("/admin/applications/{$application->id}");

        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('時間修正');
    }

    /** @test */
    public function 修正申請の承認処理が正しく行われる()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'clock_in'  => '09:00',
            'clock_out' => '18:00',
        ]);

        $application = Application::factory()->create([
            'attendance_id' => $attendance->id,
            'clock_in'      => '10:00',
            'clock_out'     => '19:00',
            'status'        => 'pending',
        ]);

        $this->post("/admin/applications/approve/{$application->id}");

        // 申請ステータス更新
        $this->assertDatabaseHas('applications', [
            'id'     => $application->id,
            'status' => 'approved',
        ]);

        // 勤怠情報が修正されている
        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'clock_in'  => '10:00',
            'clock_out' => '19:00',
        ]);
    }
}
