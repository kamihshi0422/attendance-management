@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<section class="wrapper">

    <header class="ttl-box">
        <h1 class="ttl">勤怠詳細</h1>
    </header>

    <form method="POST" action="{{ $formAction }}">
        @csrf

        <input type="hidden" name="work_date" value="{{ $rawDate }}">

        <fieldset @if($isDisabled === true) disabled @endif>
            <section class="table-wrapper">
                <table class="table">

                    {{-- 名前 --}}
                    <tr>
                        <th>名前</th>
                        <td>
                            <span class="name">{{ $user_name }}</span>
                            <span class="span"></span>
                            <span class="span"></span>
                        </td>
                    </tr>

                    <tr>
                        <th>日付</th>
                        <td>
                            <span class="yearPart">{{ $yearPart }}</span>
                            <span class="span"></span>
                            <span class="datePart">{{ $datePart }}</span>
                        </td>
                    </tr>

                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            <input type="text" name="clock_in" value="{{ old('clock_in', $clock_in) }}">
                            <span class="span">~</span>
                            <input type="text" name="clock_out" value="{{ old('clock_out', $clock_out) }}">

                            @if ($errors->has('clock_in'))
                                <p class="error-message">{{ $errors->first('clock_in') }}</p>
                            @elseif ($errors->has('clock_out'))
                                <p class="error-message">{{ $errors->first('clock_out') }}</p>
                            @endif
                        </td>
                    </tr>

                    @foreach($breaks as $index => $break)
                        <tr>
                            <th>休憩{{ $index === 0 ? '' : $index + 1 }}</th>
                            <td>
                                <input type="text" name="break_start[{{ $index }}]" value="{{ old("break_start.$index", $break['start']) }}">
                                <span class="span">~</span>
                                <input type="text" name="break_end[{{ $index }}]"   value="{{ old("break_end.$index", $break['end']) }}">

                                @if ($errors->has("break_start.$index"))
                                    <p class="error-message">
                                        {{ $errors->first("break_start.$index") }}
                                    </p>
                                @elseif ($errors->has("break_end.$index"))
                                    <p class="error-message">
                                        {{ $errors->first("break_end.$index") }}
                                    </p>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    {{-- 備考 --}}
                    <tr>
                        <th>備考</th>
                        <td>
                            @if($isDisabled)
                                <p class="readonly-reason">{{ $reason }}</p>
                            @else
                                <textarea name="reason" rows="3">{{ old('reason', $reason) }}</textarea>
                            @endif

                            @error('reason')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                </table>
            </section>
        </fieldset>

        @if(($mode ?? 'edit') === 'approve')

            @if(isset($isApproved) && $isApproved === true)
                <div class="btn-wrapper">
                    <p class="approved-text">承認済み</p>
                </div>
            @else
                <div class="btn-wrapper">
                    <button type="submit">承認</button>
                </div>
        @endif

        @elseif($pending === true)
                <p class="pending-text">*承認待ちのため修正はできません。</p>
            @else
                <div class="btn-wrapper">
                    <button type="submit">修正</button>
                </div>
        @endif
    </form>

</section>
@endsection
