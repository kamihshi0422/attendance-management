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
            'name' => '管理者',
            'email' => 'host@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        // その他ランダムユーザー
        User::factory(5)->create();
    }
}
