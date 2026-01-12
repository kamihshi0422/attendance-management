<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function メールアドレスが未入力の場合バリデーションメッセージが表示される()
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function パスワードが未入力の場合バリデーションメッセージが表示される()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function 登録内容と一致しない場合バリデーションメッセージが表示される()
    {
        User::factory()->admin()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors();
    }
}
