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

        // 他ユーザーの承認待ち申請
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Application::factory()->create([
            'user_id' => $user1->id,
            'status'  => '承認待ち',
            'reason'  => '申請A',
        ]);

        Application::factory()->create([
            'user_id' => $user2->id,
            'status'  => '承認待ち',
            'reason'  => '申請B',
        ]);

        // 承認済み（表示されてはいけない）
        Application::factory()->create([
            'status' => '承認済み',
            'reason' => '承認済み申請',
        ]);

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        // 承認待ちは全件表示される
        $response->assertSee('申請A');
        $response->assertSee('申請B');

        // 承認済みは表示されない
        $response->assertDontSee('承認済み申請');
    }

    /** @test */
    public function 承認済みの修正申請が全て表示されている()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // 承認済み（表示される）
        Application::factory()->create([
            'user_id' => $user1->id,
            'status'  => '承認済み',
            'reason'  => '承認済み申請A',
        ]);

        Application::factory()->create([
            'user_id' => $user2->id,
            'status'  => '承認済み',
            'reason'  => '承認済み申請B',
        ]);

        // 承認待ち（表示されない）
        Application::factory()->create([
            'status' => '承認待ち',
            'reason' => '未承認申請',
        ]);

        $response = $this->get('/stamp_correction_request/list?status=approved');

        $response->assertStatus(200);

        // 承認済みは全件表示
        $response->assertSee('承認済み申請A');
        $response->assertSee('承認済み申請B');

        // 承認待ちは表示されない
        $response->assertDontSee('未承認申請');
    }

    /** @test */
    public function 修正申請の詳細内容が正しく表示されている()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'clock_in'  => '2026-01-16 09:00:00',
            'clock_out' => '2026-01-16 18:00:00',
        ]);

        $application = Application::factory()->create([
            'attendance_id'       => $attendance->id,
            'corrected_clock_in'  => '2026-01-16 10:00:00',
            'corrected_clock_out' => '2026-01-16 19:00:00',
            'reason'              => '時間修正',
            'status'              => '承認待ち',
        ]);

        $response = $this->get(
            "/stamp_correction_request/approve/{$application->id}"
        );

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
            'clock_in'  => '2026-01-16 09:00:00',
            'clock_out' => '2026-01-16 18:00:00',
        ]);

        $application = Application::factory()->create([
            'attendance_id'       => $attendance->id,
            'corrected_clock_in'  => '2026-01-16 10:00:00',
            'corrected_clock_out' => '2026-01-16 19:00:00',
            'status'              => '承認待ち',
        ]);

        $this->post(
            "/stamp_correction_request/approve/{$application->id}"
        );

        $this->assertDatabaseHas('applications', [
            'id'     => $application->id,
            'status' => '承認済み',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'clock_in'  => '2026-01-16 10:00:00',
            'clock_out' => '2026-01-16 19:00:00',
        ]);
    }
}
