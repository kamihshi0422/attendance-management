<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ApplicationRequest;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Application;

class UserApplicationController extends Controller
{
    // 勤怠詳細から修正申請送信
    public function submitCorrection(ApplicationRequest $request, $attendanceId)
    {
        $attendance = Attendance::with('application.applicationBreaks')
            ->findOrFail($attendanceId);

        // 最新申請（未承認でも承認済みでも上書き可能）
        $application = $attendance->application ?? new Application(['attendance_id' => $attendance->id]);
        $application->user_id = auth()->id();

        $workDate = Carbon::parse($attendance->work_date);

        $clockIn  = Carbon::createFromFormat('H:i', $request->clock_in)
            ->setDate($workDate->year, $workDate->month, $workDate->day);
        $clockOut = Carbon::createFromFormat('H:i', $request->clock_out)
            ->setDate($workDate->year, $workDate->month, $workDate->day);

        // 日跨ぎ処理を削除 → clockOut < clockIn の場合はバリデーションで弾かれる

        $application->corrected_clock_in  = $clockIn;
        $application->corrected_clock_out = $clockOut;
        $application->reason              = $request->reason;
        $application->status              = '承認待ち';
        $application->save();

        // 休憩は既存の未承認申請分を削除して再作成
        $application->applicationBreaks()->delete();

        // 休憩登録（日跨ぎ対応なし）
        foreach ($request->break_start ?? [] as $key => $startInput) {
            $endInput = $request->break_end[$key] ?? null;
            if (!$startInput && !$endInput) continue;

            $start = Carbon::createFromFormat('H:i', $startInput)
                ->setDate($workDate->year, $workDate->month, $workDate->day);

            $end = $endInput
                ? Carbon::createFromFormat('H:i', $endInput)
                    ->setDate($workDate->year, $workDate->month, $workDate->day)
                : null;

            $application->applicationBreaks()->create([
                'break_start' => $start,
                'break_end'   => $end,
            ]);
        }

        return redirect()->back();
    }

    // 勤怠詳細から新規勤怠作成
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

        // application がなければ新規作成、あれば上書き
        $application = $attendance->application ?? new Application(['attendance_id' => $attendance->id]);
        $application->user_id = $user->id;
        $application->corrected_clock_in  = Carbon::parse($request->work_date.' '.$request->clock_in);
        $application->corrected_clock_out = Carbon::parse($request->work_date.' '.$request->clock_out);
        $application->reason = $request->reason;
        $application->status = '承認待ち';
        $application->save();

        // 休憩も上書き（日跨ぎ処理なし）
        $application->applicationBreaks()->delete();
        foreach ($request->break_start ?? [] as $key => $startInput) {
            $endInput = $request->break_end[$key] ?? null;
            if (!$startInput && !$endInput) continue;

            $start = Carbon::parse($request->work_date.' '.$startInput);
            $end   = $endInput ? Carbon::parse($request->work_date.' '.$endInput) : null;

            $application->applicationBreaks()->create([
                'break_start' => $start,
                'break_end'   => $end,
            ]);
        }

        return redirect()->route('attendanceDetail.show', $attendance->id);
    }

    public function showApplicationList(Request $request)
    {
        $status = $request->query('status', 'pending');

        $applicationQuery = Application::with(['user', 'attendance'])
            ->orderBy('created_at', 'asc');

        // 一般ユーザーなら自分の分だけ
        if (auth()->user()->role === 'user') {
            $applicationQuery->where('user_id', auth()->id());
        }

        // ステータス絞り込み
        if ($status === 'approved') {
            $applicationQuery->where('status', '承認済み');
        } else {
            $applicationQuery->where('status', '承認待ち');
        }

        return view('application_list', [
            'applicationList' => $applicationQuery->get(),
            'status' => $status,
        ]);
    }
}
