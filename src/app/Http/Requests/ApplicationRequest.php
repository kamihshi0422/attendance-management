<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class ApplicationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'clock_in'      => ['required', 'regex:/^([0-1]?\d|2[0-3]):[0-5]\d$/'],
            'clock_out'     => ['required', 'regex:/^([0-1]?\d|2[0-3]):[0-5]\d$/'],
            'break_start.*' => ['nullable', 'regex:/^([0-1]?\d|2[0-3]):[0-5]\d$/'],
            'break_end.*'   => ['nullable', 'regex:/^([0-1]?\d|2[0-3]):[0-5]\d$/'],
            'reason'        => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'clock_in.required'      => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in.regex'         => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.required'     => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.regex'        => '出勤時間もしくは退勤時間が不適切な値です',
            'break_start.*.regex'    => '休憩時間が不適切な値です',
            'break_end.*.regex'      => '休憩時間もしくは退勤時間が不適切な値です',
            'reason.required'        => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $clockIn  = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            // -----------------------------
            // 出勤・退勤の論理チェック
            // -----------------------------
            if ($clockIn && $clockOut) {
                try {
                    $in  = Carbon::createFromFormat('H:i', $clockIn);
                    $out = Carbon::createFromFormat('H:i', $clockOut);
                } catch (\Exception  $exception) {
                    $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                    return;
                }

                if ($out->lt($in)) {
                    // 出勤 > 退勤の場合
                    $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            // -----------------------------
            // 休憩時間チェック
            // -----------------------------
            $breakStarts = $this->input('break_start', []);
            $breakEnds   = $this->input('break_end', []);

            foreach ($breakStarts as $index => $start) {
                $end = $breakEnds[$index] ?? null;

                // 両方空ならスキップ
                if (($start === null || $start === '') && ($end === null || $end === '')) {
                    continue;
                }

                // 片方だけ入力はNG
                if (($start === null || $start === '') || ($end === null || $end === '')) {
                    $validator->errors()->add("break_start.$index", '休憩時間が不適切な値です');
                    continue;
                }

                try {
                    $startTime = Carbon::createFromFormat('H:i', $start);
                    $endTime   = Carbon::createFromFormat('H:i', $end);
                } catch (\Exception $exception) {
                    $validator->errors()->add("break_start.$index", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩開始は出勤 <= 開始 <= 退勤
                if ($startTime->lt($in) || $startTime->gt($out)) {
                    $validator->errors()->add("break_start.$index", '休憩時間が不適切な値です');
                }

                // 休憩終了は開始 <= 終了 <= 退勤
                if ($endTime->lt($startTime) || $endTime->gt($out)) {
                    $validator->errors()->add("break_end.$index", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }

            // -----------------------------
            // 備考欄必須は rules でカバー済み
            // -----------------------------
        });
    }
}
