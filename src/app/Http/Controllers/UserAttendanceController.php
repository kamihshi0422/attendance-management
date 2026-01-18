<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceDetailViewService;
use App\Services\AttendanceMonthlyService;
use App\Services\AttendanceTimeService;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Application;

class UserAttendanceController extends Controller
{
    public function showAttendance()
    {
        Carbon::setLocale('ja');
        $currentDate = Carbon::now()->translatedFormat('Y年n月j日(D)');
        $currentTime = Carbon::now()->format('H:i');

        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', today())
            ->first();

        if ($attendance) {
            $status = $attendance->status;
        } else {
            $status = '勤務外';
        }

        return view('attendance', compact('currentDate', 'currentTime', 'status'));
    }

    public function clockIn()
    {
        $userId = auth()->id();

        $attendance = Attendance::where('user_id', $userId)
                        ->whereDate('work_date', today())
                        ->first();

        if ($attendance) {
            return back()->withErrors(['出勤' => '今日はすでに出勤済みです。']);
        }

        Attendance::create([
            'user_id' => $userId,
            'work_date' => today(),
            'clock_in' => now(),
            'status' => '出勤中',
        ]);

        return redirect('/attendance');
    }

    public function clockOut()
    {
        $attendance = Attendance::where('user_id', auth()->id())
                                ->whereDate('work_date', today())
                                ->first();

        if (!$attendance) {
            return back()->with('error', '出勤中のみ退勤できます。');
        }

        if ($attendance->status !== '出勤中') {
            return back()->with('error', '出勤中のみ退勤できます。');
        }

        $attendance->update([
            'clock_out' => now(),
            'status' => '退勤済',
        ]);

        return redirect('/attendance');
    }

    public function startBreak()
    {
        $attendance = Attendance::where('user_id', auth()->id())
                                ->whereDate('work_date', today())
                                ->first();

        if (!$attendance) {
            return back()->with('error', '出勤中のみ休憩開始できます。');
        }

        if ($attendance->status !== '出勤中') {
            return back()->with('error', '出勤中のみ休憩開始できます');
        }

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        $attendance->update([
            'status' => '休憩中',
        ]);

        return redirect('/attendance');
    }

    public function endBreak()
    {
        $attendance = Attendance::where('user_id', auth()->id())
                                ->whereDate('work_date', today())
                                ->first();

        if (!$attendance) {
            return back()->with('error', '休憩中のみ休憩終了できます。');
        }

        if ($attendance->status !== '休憩中') {
            return back()->with('error', '休憩中のみ休憩終了できます。');
        }

        $breakTime = BreakTime::where('attendance_id', $attendance->id)
                                ->whereNull('break_end')
                                ->first();

        if ($breakTime) {
            $breakTime->update([
                'break_end' => now(),
            ]);
        }

        $attendance->update([
            'status' => '出勤中',
        ]);

        return redirect('/attendance');
    }

    public function showAttendanceList(
        Request $request,
        AttendanceMonthlyService $monthlyService,
        AttendanceTimeService $timeService,
    ) {
        Carbon::setLocale('ja');

        $user = auth()->user();

        $currentMonth = $request->year && $request->month
        ? Carbon::create($request->year, $request->month)
        : Carbon::now();

        $previousMonth = $currentMonth->copy()->subMonth();
        $nextMonth     = $currentMonth->copy()->addMonth();

        $days = $monthlyService->build($user, $currentMonth, $timeService);

        return view('attendance_list', compact(
            'days', 'currentMonth', 'previousMonth', 'nextMonth'
        ));
    }

    public function showAttendanceDetail(
        Request $request,
        $id = null,
        AttendanceDetailViewService $detailViewService
    ) {
        $attendance = null;
        $application = null;

        if ($id) {
            $attendance = Attendance::with(
                'breakTimes',
                'user',
                'application.applicationBreaks'
            )->findOrFail($id);

            $application = $attendance->application;
            $workDate = Carbon::parse($attendance->work_date);

        } else {
            $application = Application::with('attendance')
                ->where('user_id', auth()->id())
                ->where('status', '承認待ち')
                ->firstOrFail();

            $attendance = $application->attendance;
            $workDate = Carbon::parse($attendance->work_date);
        }

        $attendanceDetailData = $detailViewService->build(
            $attendance,
            $application,
            $workDate,
            'user'
        );

        if ($attendance) {
            $formAction = route('attendance.submitCorrection', $attendance->id);
        } else {
            $formAction = route('attendance.createForDate');
        }

        $attendanceDetailData['formAction'] = $formAction;

        return view('attendance_detail', $attendanceDetailData);
    }
}