@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<section class="wrapper">

    <header class="ttl-box">
        <h1 class="ttl">{{ $current_date->format('Y年n月j日') }}の勤怠</h1>
    </header>

    <nav class="nav-wrapper">
        <a class="sub-month" href="{{ route('admin.attendanceList.show', ['date' => $previous_date->toDateString()]) }}">
            前日
        </a>

        <div class="current-date">{{ $current_date->format('Y/m/d') }}</div>

        <a class="add-month" href="{{ route('admin.attendanceList.show', ['date' => $next_date->toDateString()]) }}">
            翌日
        </a>
    </nav>

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

        @foreach ($daily_attendance_list as $row)
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

</section>
@endsection
