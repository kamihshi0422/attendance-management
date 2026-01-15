<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Application;
use Carbon\Carbon;

class AttendanceEditTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post(route('attendance.submitCorrection', $attendance->id), [
            'clock_in'    => '18:00',
            'clock_out'   => '09:00',
            'reason'      => '修正理由',
            'break_start' => [],
            'break_end'   => [],
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post(route('attendance.submitCorrection', $attendance->id), [
            'clock_in'    => '09:00',
            'clock_out'   => '18:00',
            'reason'      => '修正理由',
            'break_start' => ['19:00'],
            'break_end'   => ['19:30'],
        ]);

        $response->assertSessionHasErrors([
            'break_start.0' => '休憩時間が不適切な値です',
        ]);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post(route('attendance.submitCorrection', $attendance->id), [
            'clock_in'    => '09:00',
            'clock_out'   => '18:00',
            'reason'      => '修正理由',
            'break_start' => ['17:00'],
            'break_end'   => ['19:00'],
        ]);

        $response->assertSessionHasErrors([
            'break_end.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /** @test */
    public function 備考欄が未入力の場合エラーメッセージが表示される()
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->post(route('attendance.submitCorrection', $attendance->id), [
            'clock_in'    => '09:00',
            'clock_out'   => '18:00',
            'reason'      => '',
            'break_start' => [],
            'break_end'   => [],
        ]);

        $response->assertSessionHasErrors([
            'reason' => '備考を記入してください',
        ]);
    }

    /** @test */
    public function 修正申請処理が実行される()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post(route('attendance.submitCorrection', ['attendance' => $attendance->id]), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_start' => ['12:00'],
            'break_end' => ['13:00'],
            'reason' => '修正理由',
        ]);

        $application = Application::where('attendance_id', $attendance->id)->first();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->get(route('applicationApproval.show', ['attendance_correct_request_id' => $application->id]));
        $response->assertStatus(200);
        $response->assertSee('修正理由');

        $response = $this->get(route('applicationList.show', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertSee((string)$application->id);
    }

    /** @test */
    public function 承認待ちにログインユーザーが行った申請が全て表示されている()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $applications = Application::where('user_id', $user->id)
            ->where('status', '承認待ち')
            ->get();

        $response = $this->get(route('applicationList.show', ['status' => 'pending']));
        $response->assertStatus(200);

        foreach ($applications as $app) {
            $response->assertSee((string)$app->id);
        }
    }

    /** @test */
    public function 承認済みに管理者が承認した修正申請が全て表示されている()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $applications = Application::where('status', '承認済み')->get();

        $response = $this->get(route('applicationList.show', ['status' => 'approved']));
        $response->assertStatus(200);

        foreach ($applications as $app) {
            $response->assertSee((string)$app->id);
        }
    }

    /** @test */
    public function 各申請の詳細を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create(['user_id' => $user->id]);
        $application = Application::factory()->create([
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'status'        => '承認待ち',
        ]);

        $responseList = $this->get(route('applicationList.show', ['status' => 'pending']));
        $responseList->assertStatus(200);

        $responseDetail = $this->get(route('attendanceDetail.show', $attendance->id));
        $responseDetail->assertStatus(200);
        $responseDetail->assertSee($attendance->work_date->format('Y-m-d'));
    }
}
