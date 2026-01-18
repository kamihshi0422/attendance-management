<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
