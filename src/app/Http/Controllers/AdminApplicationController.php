<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceDetailService;
use Carbon\Carbon;
use App\Models\Application;
use Illuminate\Support\Facades\DB;

class AdminApplicationController extends Controller
{
public function showApplicationApproval(
    Request $request,
    $applicationId,
    AttendanceDetailService $service
) {
    $application = Application::with([
        'attendance.breakTimes',
        'attendance.user',
        'applicationBreaks',
    ])->findOrFail($applicationId);

    $attendance = $application->attendance;

    $data = $service->build(
        $attendance,
        $application,
        Carbon::parse($attendance->work_date),
        'approve'
    );

    $data['mode'] = 'approve';
    $data['formAction'] = route('application.approve', $application->id);

    return view('attendance_detail', $data);
}

public function applicationApproval($applicationId)
{
    $application = Application::with('attendance')->findOrFail($applicationId);

    DB::transaction(function () use ($application) {

        $attendance = $application->attendance;

        // 出勤・退勤を反映
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

        // ★ 休憩を反映

        // ① 既存の休憩を全削除
        $attendance->breakTimes()->delete();

        // ② 修正申請の休憩をコピー
        foreach ($application->applicationBreaks as $applicationBreak) {

            // 空行はスキップ
            if (!$applicationBreak->break_start && !$applicationBreak->break_end) {
                continue;
            }

            $attendance->breakTimes()->create([
                'break_start' => $applicationBreak->break_start,
                'break_end'   => $applicationBreak->break_end,
            ]);
        }

        // ★ ステータスを承認済みに（❷）
        $application->status = '承認済み';
        $application->save();
    });

    return redirect()->route('applicationApproval.show', $application->id);
}

}