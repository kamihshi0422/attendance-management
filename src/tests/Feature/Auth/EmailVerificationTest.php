<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 会員登録後認証メールが送信される()
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        event(new Registered($user));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function メール認証導線画面のボタン押下で認証サイトに遷移する()
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        $response = $this->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee('認証はこちらから');

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $verifyResponse = $this->get($verificationUrl);

        $verifyResponse->assertRedirect('/attendance');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** @test */
    public function メール認証完了後に勤怠登録画面に遷移する()
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/attendance');
        $attendancePage = $this->get('/attendance');
        $attendancePage->assertStatus(200);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
