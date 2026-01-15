@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')

<div class="wrapper">
    <div class="ttl-box">
        <h1 class="ttl">{{ $currentDate->format('Y年n月j日') }}の勤怠</h1>
    </div>
    <div class="nav-wrapper">
        <a class="sub-month" href="{{ route('admin.attendanceList.show', ['date' => $previousDate->toDateString()]) }}">
            前日
        </a>

        <div class="current-date">{{ $currentDate->format('Y/m/d') }}</div>

        <a class="add-month" href="{{ route('admin.attendanceList.show', ['date' => $nextDate->toDateString()]) }}">
            翌日
        </a>
    </div>

    <section class="table-wrapper">
        <table class="table">
        <tr>
            <th>名前</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>

        @foreach ($attendanceListForOneDay as $row)
            <tr>
                <td>{{ $row['user_name'] }}</td>
                <td>{{ $row['clock_in'] }}</td>
                <td>{{ $row['clock_out'] }}</td>
                <td>{{ $row['break_time'] }}</td>
                <td>{{ $row['total_work_time'] }}</td>
                <td>
                    <a href="{{ route('admin.attendanceDetail.show', [
                        'id' => $row['attendance_id'],
                        'date' => $row['work_date']
                    ]) }}">
                        詳細
                    </a>
                </td>
            </tr>
        @endforeach
        </table>
    </section>
</div>
@endsection
