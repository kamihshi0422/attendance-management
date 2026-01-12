<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ApplicationRequest;
use App\Services\AttendanceDetailService;
use App\Services\AttendanceMonthlyService;
use App\Services\AttendanceTimeService;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    /**
     * 管理者用：1日分の全ユーザー勤怠
     */
    //requestで$year = null を書かなくてもエラーにならない
public function showAdminAttendanceList(
    Request $request,
    AttendanceTimeService $timeService
) {
        Carbon::setLocale('ja');

        // 現在の日付（URLパラメータ or 今日）
        $currentDate = $request->date
            ? Carbon::parse($request->date) //URL に date パラメータがあればそれを日付として使用。
            : Carbon::today(); //ない場合は 今日の日付

        $previousDate = $currentDate->copy()->subDay(); //subDay() → 前日
        $nextDate     = $currentDate->copy()->addDay(); //addDay() → 翌日

        // その日の勤怠データ（ユーザー情報含む）
        $attendanceRecords = Attendance::with('user', 'breakTimes')
            ->where('work_date', $currentDate->toDateString()) //特定日の勤怠だけ取得
            ->get();

        // 表示用配列（UI 用） 空の配列（Array）を作成
        $attendanceListForOneDay = [];

            foreach ($attendanceRecords as $attendance) {

        $times = $timeService->calculate($attendance);

        $attendanceListForOneDay[] = [
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
            'attendanceListForOneDay' => $attendanceListForOneDay,
            'currentDate' => $currentDate,
            'previousDate' => $previousDate,
            'nextDate' => $nextDate,
        ]);
    }

public function showAdminDetail(
    Request $request,
    $id,
    AttendanceDetailService $service
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
        // 既存勤怠
        $attendance = Attendance::with([
            'breakTimes',
            'user',
            'application.applicationBreaks'
        ])->findOrFail($id);
    }

    $data = $service->build(
        $attendance,
        $attendance->application,
        $workDate,
        'admin'
    );

    $data['formAction'] = route(
        'admin.submitCorrection',
        $attendance->id
    );

    return view('attendance_detail', $data);
}

    // 勤怠詳細から修正申請送信
    // 管理者用 勤怠修正（即時反映）
    public function submitAdminCorrection(ApplicationRequest $request, $attendanceId)
    {
        $attendance = Attendance::with([
            'breakTimes',
            'application',
        ])->findOrFail($attendanceId);

        DB::transaction(function () use ($attendance, $request) {

            // 画面基準日（← 超重要）
            $workDate = Carbon::parse($request->work_date);

            // 出勤・退勤
            $clockIn = Carbon::createFromFormat('H:i', $request->clock_in)
                ->setDate($workDate->year, $workDate->month, $workDate->day);

            $clockOut = Carbon::createFromFormat('H:i', $request->clock_out)
                ->setDate($workDate->year, $workDate->month, $workDate->day);

            // 日跨ぎ
            if ($clockOut->lt($clockIn)) {
                $clockOut->addDay();
            }

            // 勤怠を直接更新
            $attendance->update([
                'work_date' => $workDate->toDateString(),
                'clock_in'  => $clockIn,
                'clock_out' => $clockOut,
            ]);

            // 既存休憩を削除（管理者は全上書き）
            $attendance->breakTimes()->delete();

            // 休憩登録（日跨ぎ対応）
            foreach ($request->break_start ?? [] as $key => $startInput) {

                $endInput = $request->break_end[$key] ?? null;

                if (!$startInput && !$endInput) continue;

                $start = Carbon::createFromFormat('H:i', $startInput)
                    ->setDate($workDate->year, $workDate->month, $workDate->day);

                if ($start->lt($clockIn)) {
                    $start->addDay();
                }

                $end = null;
                if ($endInput) {
                    $end = Carbon::createFromFormat('H:i', $endInput)
                        ->setDate($workDate->year, $workDate->month, $workDate->day);

                    if ($end->lt($start)) {
                        $end->addDay();
                    }
                }

                $attendance->breakTimes()->create([
                    'break_start' => $start,
                    'break_end'   => $end,
                ]);
            }

            // ⭐ 管理者修正が入ったら申請は完全削除
            if ($attendance->application) {
                $attendance->application()->delete();
            }
        });

        return redirect()->back();
    }

    public function showStaffList()
    {
        // スタッフ一覧取得（必要なカラムだけ）
        $staffs = User::select('id', 'name', 'email')->get();

        return view('staff_list', [
            'staffs' => $staffs,
        ]);
    }

    // スタッフ勤怠一覧表示
public function showStaffAttendance(
    Request $request,
    $id,
    AttendanceMonthlyService $monthlyService,
    AttendanceTimeService $timeService
) {
        Carbon::setLocale('ja');

        $staff = User::findOrFail($id);

        $currentMonth = $request->year && $request->month
            ? Carbon::create($request->year, $request->month)
            : Carbon::now();

        $previousMonth = $currentMonth->copy()->subMonth();
        $nextMonth     = $currentMonth->copy()->addMonth();

        // ⭐ Service で月次生成
        $days = $monthlyService->build($staff, $currentMonth, $timeService);

        return view('staff_attendance', compact(
            'staff', 'days', 'currentMonth', 'previousMonth', 'nextMonth'
        ));
    }

    // CSV出力
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

        // 共通メソッドで $days を生成
        $days = $monthlyService->build($staff, $currentMonth, $timeService); //スタッフと日付を指定して　日ごとの配列 $days に整形

        $filename = $staff->name . '_' . $currentMonth->format('Y_m') . '_attendance.csv';

        $headers = [
            'Content-Type' => 'text/csv', //送信するデータの種類を伝える
            'Content-Disposition' => "attachment; filename={$filename}", //attachment 添付ファイルとして扱う指示&ファイル名を指定
        ];

        $callback = function() use ($days) { //無名関数 $days をループしてCSVに書き出す
            $file = fopen('php://output', 'w'); //fopen() は ファイルを開く関数  php://output：特別なストリームで ブラウザに直接書き込む場所 w =write

            // ヘッダー
            fputcsv($file, ['日付', '出勤', '退勤', '休憩', '合計']); //配列をCSV形式に変換して書き込む関数

            // データ行
            foreach ($days as $day) {
                fputcsv($file, [
                    $day['weekday'],
                    $day['clock_in'],
                    $day['clock_out'],
                    $day['break'],
                    $day['total'],
                ]);
            }

            fclose($file); //fopen('php://output', 'w') で開いたストリームは 最後に閉じる必要 がある
        };

        return response()->stream($callback, 200, $headers); //response() → 「HTTPで返すよ！」とLaravelに伝える +streamCSVデータはこれから少しずつ送る
        // $callback → データを作る処理（CSV生成）
        //  200 → 「OKだよ」というHTTPステータス
        //  $headers → ブラウザへの追加情報（ファイル名や形式）
    }

}