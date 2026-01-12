<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AttendanceDetailService;
use App\Services\AttendanceMonthlyService;
use App\Services\AttendanceTimeService;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;

class UserAttendanceController extends Controller
{
    public function showAttendance() //12/13
    {
        Carbon::setLocale('ja'); //(D)の日本語化
        $currentDate = Carbon::now()->translatedFormat('Y年n月j日(D)');
        $currentTime = Carbon::now()->format('H:i');

        $attendance = Attendance::where('user_id', auth()->id()) //→ ログイン中のユーザーだけ
            ->whereDate('work_date', today()) //→ work_date が 今日の日付のもの
            ->first(); //1件だけ取得

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

        // 今日の出勤レコードがあるか
        $attendance = Attendance::where('user_id', $userId)
                        ->whereDate('work_date', today())
                        ->first();

        if ($attendance) {
            // セッションにエラーを設定
            return back()->withErrors(['出勤' => '今日はすでに出勤済みです。']);
        }

        // 新規出勤
        Attendance::create([
            'user_id' => $userId,
            'work_date' => today(), // 出勤日
            'clock_in' => now(), // 打刻時間
            'status' => '出勤中',
        ]);

        return redirect('/attendance');
    }

    public function clockOut()
    {
        $attendance = Attendance::where('user_id', auth()->id())
                                ->whereDate('work_date', today())
                                ->first();

        //勤怠がないならNG
        if (!$attendance) { //nullだと起動
            return back()->with('error', '出勤中のみ退勤できます。');
        }

        //ステータスが出勤中じゃないならNG
        if ($attendance->status !== '出勤中') { //「等しくない」＋「型も違う」
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

        //勤怠がないならNG
        if (!$attendance) { //nullだと起動
            return back()->with('error', '出勤中のみ休憩開始できます。');
        }

        //ステータスが出勤中じゃないならNG
        if ($attendance->status !== '出勤中') { //「等しくない」＋「型も違う」
            return back()->with('error', '出勤中のみ休憩開始できます');
        } //休憩中休憩増やせないためlatestはいらない

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

            //勤怠がないならNG
        if (!$attendance) { //nullだと起動
            return back()->with('error', '休憩中のみ休憩終了できます。');
        }

        //ステータスが休憩中じゃないならNG
        if ($attendance->status !== '休憩中') { //「等しくない」＋「型も違う」
            return back()->with('error', '休憩中のみ休憩終了できます。');
        } //休憩中休憩増やせないためlatestはいらない

        $breakTime = BreakTime::where('attendance_id', $attendance->id)
                                ->whereNull('break_end') //break_end が NULL のものだけ
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

        // 表示月（未指定なら今月）
        $currentMonth = $request->year && $request->month
        ? Carbon::create($request->year, $request->month)
        : Carbon::now();

        $previousMonth = $currentMonth->copy()->subMonth(); //前月へ 引き算 copy()：元の $currentMonth を壊さないため
        $nextMonth     = $currentMonth->copy()->addMonth(); //翌月へ 足し算

        // ⭐ 月次勤怠生成（Admin と全く同じ）
        $days = $monthlyService->build($user, $currentMonth, $timeService);

        // Blade に渡す
        return view('attendance_list', compact(
            'days', 'currentMonth', 'previousMonth', 'nextMonth'
        ));
    }

public function showAttendanceDetail(
    Request $request,
    $id = null,
    AttendanceDetailService $service
) {
    $attendance = $id
        ? Attendance::with(
            'breakTimes',
            'user',
            'application.applicationBreaks'
        )->find($id)
        : null;

    $data = $service->build(
        $attendance,
        
        $attendance?->application,
        Carbon::parse($request->date),
        'user'
    );

    $data['formAction'] = $attendance
        ? route('attendance.submitCorrection', $attendance->id)
        : route('attendance.createForDate');

    return view('attendance_detail', $data);
}
}