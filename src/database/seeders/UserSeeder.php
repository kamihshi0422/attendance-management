<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // ログイン確認用の固定ユーザー
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'email_verified_at' =>'ok',
        ]);

        User::factory()->create([
            'name' => 'Test',
            'email' => 'test@test',
            'password' => bcrypt('testtest'),
            'role' => 'user',
            'email_verified_at' =>'ok',
        ]);
        // その他ランダムユーザー
        User::factory(5)->create();
    }
}
