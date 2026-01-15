<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Application;

class AttendanceDetailService
{
    public function build(
        ?Attendance $attendance,
        ?Application $application,
        Carbon $workDate,
        string $mode = 'user'
    ): array {

        /*
        |--------------------------------------------------------------------------
        | application を勤怠値の上書きに使うかどうか
        |--------------------------------------------------------------------------
        | ・承認待ちのみ有効
        | ・admin モードでは使わない
        */
        $useApplication =
            $application
            && $application->status === '承認待ち'
            && $mode !== 'admin';

        // ユーザー名
        $user_name = $attendance?->user->name ?? auth()->user()->name;

        // 日付
        $yearPart = $workDate->translatedFormat('Y年');
        $datePart = $workDate->translatedFormat('n月j日');
        $rawDate  = $workDate->toDateString();

        // 出勤・退勤
        $clock_in = '';
        $clock_out = '';

        if ($useApplication) {
            if ($application->corrected_clock_in) {
                $clock_in = Carbon::parse($application->corrected_clock_in)->format('H:i');
            }
            if ($application->corrected_clock_out) {
                $clock_out = Carbon::parse($application->corrected_clock_out)->format('H:i');
            }
        }

        if ($clock_in === '' && $attendance?->clock_in) {
            $clock_in = Carbon::parse($attendance->clock_in)->format('H:i');
        }
        if ($clock_out === '' && $attendance?->clock_out) {
            $clock_out = Carbon::parse($attendance->clock_out)->format('H:i');
        }

        // 休憩
        $breaks = [];

        $sourceBreaks =
            ($useApplication && $application->applicationBreaks->isNotEmpty())
                ? $application->applicationBreaks
                : ($attendance?->breakTimes ?? collect());

        foreach ($sourceBreaks as $break) {
            $start = $break->break_start
                ? Carbon::parse($break->break_start)->format('H:i')
                : '';
            $end = $break->break_end
                ? Carbon::parse($break->break_end)->format('H:i')
                : '';

            if ($start !== '' || $end !== '') {
                $breaks[] = [
                    'id'    => $break->id,
                    'start' => $start,
                    'end'   => $end,
                ];
            }
        }

        // 状態判定
        $pending    = $application?->status === '承認待ち';
        $isApproved = $application?->status === '承認済み';

        $isDisabled =
            $mode === 'approve'
            || $pending;

        if (count($breaks) === 0) {
            $breaks[] = ['id' => null, 'start' => '', 'end' => ''];
        }

        // ⭐ 編集可能なときだけ +1 行
        if ($isDisabled === false) {
            $breaks[] = [
                'id'    => null,
                'start' => '',
                'end'   => '',
            ];
        }

        return [
            'user_name'  => $user_name,
            'yearPart'   => $yearPart,
            'datePart'   => $datePart,
            'rawDate'    => $rawDate,
            'clock_in'   => $clock_in,
            'clock_out'  => $clock_out,
            'breaks'     => $breaks,
            'pending'    => $pending,
            'isDisabled' => $isDisabled,
            'isApproved' => $isApproved, // ★ 追加
            'reason' => $useApplication
                        ? $application?->reason
                        : ($attendance?->reason ?? ''),
        ];
    }
}
