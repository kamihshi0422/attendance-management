<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Application;

class AttendanceDetailViewService
{
    public function build(
        ?Attendance $attendance,
        ?Application $application,
        Carbon $workDate,
        string $mode = 'user'
    ): array {
        $useApplication =
            $application
            && $application->status === '承認待ち'
            && $mode !== 'admin';

        $userName = $attendance?->user->name ?? auth()->user()->name;

        $yearPart = $workDate->translatedFormat('Y年');
        $datePart = $workDate->translatedFormat('n月j日');
        $rawDate  = $workDate->toDateString();

        $clockIn = '';
        $clockOut = '';

        if ($useApplication) {
            if ($application->corrected_clock_in) {
                $clockIn= Carbon::parse($application->corrected_clock_in)->format('H:i');
            }
            if ($application->corrected_clock_out) {
                $clockOut = Carbon::parse($application->corrected_clock_out)->format('H:i');
            }
        }

        if ($clockIn === '' && $attendance?->clock_in) {
            $clockIn = Carbon::parse($attendance->clock_in)->format('H:i');
        }

        if ($clockOut === '' && $attendance?->clock_out) {
            $clockOut = Carbon::parse($attendance->clock_out)->format('H:i');
        }

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

        $pending    = $application?->status === '承認待ち';
        $isApproved = $application?->status === '承認済み';

        $isDisabled =
            $mode === 'approve'
            || $pending;

        if (count($breaks) === 0) {
            $breaks[] = ['id' => null, 'start' => '', 'end' => ''];
        }

        if ($isDisabled === false) {
            $breaks[] = [
                'id'    => null,
                'start' => '',
                'end'   => '',
            ];
        }

        return [
            'user_name'  => $userName,
            'yearPart'   => $yearPart,
            'datePart'   => $datePart,
            'rawDate'    => $rawDate,
            'clock_in'   => $clockIn,
            'clock_out'  => $clockOut,
            'breaks'     => $breaks,
            'pending'    => $pending,
            'isDisabled' => $isDisabled,
            'isApproved' => $isApproved,
            'reason' => $useApplication
                        ? $application?->reason
                        : ($attendance?->reason ?? ''),
        ];
    }
}
