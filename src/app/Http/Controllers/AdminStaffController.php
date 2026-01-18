<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceMonthlyService;
use App\Services\AttendanceTimeService;
use Carbon\Carbon;
use App\Models\User;

class AdminStaffController extends Controller
{
    public function showStaffList()
    {
        $staffList = User::select('id', 'name', 'email')->get();

        return view('staff_list', ['staffList' => $staffList,]);
    }

    public function showStaffAttendance(
        Request $request,
        $id,
        AttendanceMonthlyService $monthlyService,
        AttendanceTimeService $timeService
    ) {
            Carbon::setLocale('ja');

            $staff= User::findOrFail($id);

            $currentMonth = $request->year && $request->month
                ? Carbon::create($request->year, $request->month)
                : Carbon::now();

            $previousMonth = $currentMonth->copy()->subMonth();
            $nextMonth     = $currentMonth->copy()->addMonth();

            $days = $monthlyService->build($staff, $currentMonth, $timeService);

            return view('staff_attendance', compact(
                'staff', 'days', 'currentMonth', 'previousMonth', 'nextMonth'
            ));
    }

    public function exportCsv(
        Request $request,
        $id,
        AttendanceMonthlyService $monthlyService,
        AttendanceTimeService $timeService
    ) {
        $staff = User::findOrFail($id);

        $currentMonth = $request->year && $request->month
            ? Carbon::create($request->year, $request->month)
            : Carbon::now();

        $days = $monthlyService->build($staff, $currentMonth, $timeService);

        $filename = $staff->name . '_' . $currentMonth->format('Y_m') . '_attendance.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function() use ($days) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($days as $day) {
                fputcsv($file, [
                    $day['weekday'],
                    $day['clock_in'],
                    $day['clock_out'],
                    $day['break'],
                    $day['total'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}