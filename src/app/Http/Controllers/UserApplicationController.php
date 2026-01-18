<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ApplicationRequest;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Application;

class UserApplicationController extends Controller
{
    public function submitCorrection(ApplicationRequest $request, $attendanceId)
    {
        $attendance = Attendance::with('application.applicationBreaks')
            ->findOrFail($attendanceId);

        $application = $attendance->application ?? new Application(['attendance_id' => $attendance->id]);
        $application->user_id = auth()->id();

        $workDate = Carbon::parse($attendance->work_date);

        $clockIn  = Carbon::createFromFormat('H:i', $request->clock_in)
            ->setDate($workDate->year, $workDate->month, $workDate->day);
        $clockOut = Carbon::createFromFormat('H:i', $request->clock_out)
            ->setDate($workDate->year, $workDate->month, $workDate->day);

        $application->corrected_clock_in  = $clockIn;
        $application->corrected_clock_out = $clockOut;
        $application->reason              = $request->reason;
        $application->status              = '承認待ち';
        $application->save();

        $application->applicationBreaks()->delete();

        foreach ($request->break_start ?? [] as $index => $startInput) {
            $endInput = $request->break_end[$index] ?? null;
            if (!$startInput && !$endInput) continue;

            $breakStart = Carbon::createFromFormat('H:i', $startInput)
                ->setDate($workDate->year, $workDate->month, $workDate->day);

            $breakEnd = $endInput
                ? Carbon::createFromFormat('H:i', $endInput)
                    ->setDate($workDate->year, $workDate->month, $workDate->day)
                : null;

            $application->applicationBreaks()->create([
                'break_start' => $breakStart,
                'break_end'   => $breakEnd,
            ]);
        }

        return redirect()->back();
    }

    public function createForDate(ApplicationRequest $request)
    {
        $user = auth()->user();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $request->work_date],
            [
                'clock_in'  => Carbon::parse($request->work_date.' '.$request->clock_in),
                'clock_out' => Carbon::parse($request->work_date.' '.$request->clock_out),
            ]
        );

        $application = $attendance->application ?? new Application(['attendance_id' => $attendance->id]);
        $application->user_id = $user->id;
        $application->corrected_clock_in  = Carbon::parse($request->work_date.' '.$request->clock_in);
        $application->corrected_clock_out = Carbon::parse($request->work_date.' '.$request->clock_out);
        $application->reason = $request->reason;
        $application->status = '承認待ち';
        $application->save();

        $application->applicationBreaks()->delete();
        foreach ($request->break_start ?? [] as $index => $startInput) {
            $endInput = $request->break_end[$index] ?? null;
            if (!$startInput && !$endInput) continue;

            $breakStart = Carbon::parse($request->work_date.' '.$startInput);
            $breakEnd   = $endInput ? Carbon::parse($request->work_date.' '.$endInput) : null;

            $application->applicationBreaks()->create([
                'break_start' => $breakStart,
                'break_end'   => $breakEnd,
            ]);
        }

        return redirect()->route('attendanceDetail.show', $attendance->id);
    }

    public function showApplicationList(Request $request)
    {
        $status = $request->query('status', 'pending');

        $applicationQuery = Application::with(['user', 'attendance'])
            ->orderBy('created_at', 'asc');

        if (auth()->user()->role === 'user') {
            $applicationQuery->where('user_id', auth()->id());
        }

        if ($status === 'approved') {
            $applicationQuery->where('status', '承認済み');
        } else {
            $applicationQuery->where('status', '承認待ち');
        }

        return view('application_list', [
            'application_list' => $applicationQuery->get(),
            'status' => $status,
        ]);
    }
}
