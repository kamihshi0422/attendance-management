@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<section class="attendance-container">

    <nav class="attendance-info">
        <div class="status">{{ $status }}</div>
        <div class="current-date">{{ $currentDate }}</div>
        <div class="current-time">{{ $currentTime }}</div>
    </nav>

    @if ($status === '勤務外')
        <div class="attendance-actions">
            <form action="{{ route('attendance.clockIn') }}" method="POST" class="attendance-form">
                @csrf
                <button type="submit" class="btn btn-clock-in">出勤</button>
            </form>
        </div>
    @endif

    @if ($status === '出勤中')
        <div class="attendance-actions">
            {{-- 退勤ボタン --}}
            <form action="{{ route('attendance.clockOut') }}" method="POST" class="attendance-form">
                @csrf
                <button type="submit" class="btn btn-clock-out">退勤</button>
            </form>

            {{-- 休憩ボタン --}}
            <form action="{{ route('attendance.break.start') }}" method="POST" class="attendance-form">
                @csrf
                <button type="submit" class="btn btn-break-start">休憩入</button>
            </form>
        </div>
    @endif

    @if ($status === '休憩中')
        <div class="attendance-actions">
            <form action="{{ route('attendance.break.end') }}" method="POST" class="attendance-form">
                @csrf
                <button type="submit" class="btn btn-break-end">休憩戻</button>
            </form>
        </div>
    @endif

    @if ($status === '退勤済')
        <div class="attendance-message">
            <p>お疲れ様でした。</p>
        </div>
    @endif

</section>
@endsection
