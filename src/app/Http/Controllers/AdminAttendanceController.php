<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ApplicationRequest;
use App\Services\AttendanceDetailViewService;
use App\Services\AttendanceTimeService;
use Carbon\Carbon;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    public function showAdminAttendanceList(
        Request $request,
        AttendanceTimeService $timeService
    ) {
        Carbon::setLocale('ja');

        $currentDate = $request->date
            ? Carbon::parse($request->date)
            : Carbon::today();

        $previousDate = $currentDate->copy()->subDay();
        $nextDate     = $currentDate->copy()->addDay();

        $attendances = Attendance::with('user', 'breakTimes')
            ->where('work_date', $currentDate->toDateString())
            ->get();

        $dailyAttendanceList = [];

        foreach ($attendances as $attendance) {
            $times = $timeService->calculate($attendance);
            $dailyAttendanceList[] = [
                'user_name' => $attendance->user->name,
                'clock_in'  => optional($attendance->clock_in)->format('H:i') ?? '',
                'clock_out' => optional($attendance->clock_out)->format('H:i') ?? '',
                'break_time' => $times['break_time'],
                'total_work_time' => $times['work_time'],
                'attendance_id' => $attendance->id,
                'work_date' => $currentDate->toDateString(),
            ];
        }

        return view('admin_attendance_list', [
            'daily_attendance_list' => $dailyAttendanceList,
            'current_date' => $currentDate,
            'previous_date' => $previousDate,
            'next_date' => $nextDate,
        ]);
    }

    public function showAdminDetail(
        Request $request,
        $id,
        AttendanceDetailViewService $detailViewService
    ) {
        $workDate = Carbon::parse($request->date)->startOfDay();

        if ($id == 0) {
            $attendance = Attendance::firstOrCreate(
                [
                    'user_id'   => (int) $request->user_id,
                    'work_date' => $workDate->toDateString(),
                ],
                [
                    'clock_in'  => null,
                    'clock_out' => null,
                ]
            );
        }else {
            $attendance = Attendance::with([
                'breakTimes',
                'user',
                'application.applicationBreaks'
            ])->findOrFail($id);
        }

        $detailViewData = $detailViewService->build(
            $attendance,
            $attendance->application,
            $workDate,
            'admin'
        );

        $detailViewData['formAction'] = route(
            'admin.submitCorrection',
            $attendance->id
        );

        return view('attendance_detail', $detailViewData);
    }

    public function submitAdminCorrection(ApplicationRequest $request, $attendanceId)
    {
        $attendance = Attendance::with([
            'breakTimes',
            'application',
        ])->findOrFail($attendanceId);

        DB::transaction(function () use ($attendance, $request) {
            $workDate = Carbon::parse($request->work_date);

            $clockIn = Carbon::createFromFormat('H:i', $request->clock_in)
                ->setDate($workDate->year, $workDate->month, $workDate->day);
            $clockOut = Carbon::createFromFormat('H:i', $request->clock_out)
                ->setDate($workDate->year, $workDate->month, $workDate->day);

            $attendance->update([
                'work_date' => $workDate->toDateString(),
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
                'reason'    => $request->reason,
            ]);

            $attendance->breakTimes()->delete();

            foreach ($request->break_start ?? [] as $key => $startInput) {
                $endInput = $request->break_end[$key] ?? null;

                if (!$startInput && !$endInput) {
                    continue;
                }

                $start = Carbon::createFromFormat('H:i', $startInput)
                    ->setDate($workDate->year, $workDate->month, $workDate->day);

                $end = null;
                if ($endInput) {
                    $end = Carbon::createFromFormat('H:i', $endInput)
                        ->setDate($workDate->year, $workDate->month, $workDate->day);
                }

                $attendance->breakTimes()->create([
                    'break_start' => $start,
                    'break_end'   => $end,
                ]);
            }

            if ($attendance->application) {
                $attendance->application()->delete();
            }
        });

        return redirect()->back();
    }
}