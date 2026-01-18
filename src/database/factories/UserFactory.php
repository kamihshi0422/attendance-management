<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name'              => $this->faker->name,
            'email'             => $this->faker->unique()->safeEmail,
            'password'          => Hash::make('password'),
            'role'              => 'user',
            'email_verified_at' => now(),
            'remember_token'    => Str::random(10),
        ];
    }

    public function admin()
    {
        return $this->state(function () {
            return [
                'role' => 'admin',
            ];
        });
    }

    public function unverified()
    {
        return $this->state(function () {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
