<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceDetailViewService;
use Carbon\Carbon;
use App\Models\Application;
use Illuminate\Support\Facades\DB;

class AdminApplicationController extends Controller
{
    public function showApplicationApproval(
        $applicationId,
        AttendanceDetailViewService $detailViewService
    ) {
        $application = Application::with([
            'attendance.breakTimes',
            'attendance.user',
            'applicationBreaks',
        ])->findOrFail($applicationId);

        $attendance = $application->attendance;

        $detailViewData = $detailViewService->build(
            $attendance,
            $application,
            Carbon::parse($attendance->work_date),
            'approve'
        );

        $detailViewData['mode'] = 'approve';
        $detailViewData['formAction'] = route('application.approve', $application->id);

        return view('attendance_detail', $detailViewData);
    }

    public function applicationApproval($applicationId)
    {
        $application = Application::with('attendance')->findOrFail($applicationId);

        DB::transaction(function () use ($application) {
            $attendance = $application->attendance;

            if ($application->corrected_clock_in) {
                $attendance->clock_in = $application->corrected_clock_in;
            }

            if ($application->corrected_clock_out) {
                $attendance->clock_out = $application->corrected_clock_out;
            }

            if ($application->reason !== null) {
                $attendance->reason = $application->reason;
            }

            $attendance->save();

            $attendance->breakTimes()->delete();

            foreach ($application->applicationBreaks as $applicationBreak) {
                if (!$applicationBreak->break_start && !$applicationBreak->break_end) {
                    continue;
                }

                $attendance->breakTimes()->create([
                    'break_start' => $applicationBreak->break_start,
                    'break_end'   => $applicationBreak->break_end,
                ]);
            }

            $application->status = '承認済み';
            $application->save();
        });

        return redirect()->route('applicationApproval.show', $application->id);
    }
}