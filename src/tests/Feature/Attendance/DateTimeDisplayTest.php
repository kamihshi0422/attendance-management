<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Carbon\Carbon;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 現在の日時情報がUIと同じ形式で出力されている()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Carbon::setTestNow(Carbon::create(2026, 1, 1, 9, 0));

        $response = $this->get('/attendance');

        $response->assertSee('2026年1月1日(木)');;
        $response->assertSee('09:00');
    }
}
