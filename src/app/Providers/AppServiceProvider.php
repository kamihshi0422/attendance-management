<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Attendance;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // layouts.app を使う全ビューに $status を渡す
        View::composer('layouts.app', function ($view) {
            $status = '勤務外'; // デフォルト

            if (auth()->check()) {
                $attendance = Attendance::where('user_id', auth()->id())
                    ->whereDate('work_date', today())
                    ->first();

                $status = $attendance ? $attendance->status : '勤務外';
            }

            $view->with('status', $status);
        });
    }
}
