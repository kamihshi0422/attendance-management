<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApplicationBreak extends Model
{
    protected $fillable = [
        'application_id',
        'break_start',
        'break_end'
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
