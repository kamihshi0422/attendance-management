@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<section class="wrapper">

    <header class="ttl-box">
        <h1 class="ttl">勤怠一覧</h1>
    </header>

    <nav class="nav-wrapper">
        <a class="sub-month" href="{{ route('attendanceList.show', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}">
            前月
        </a>

        <div class="current-date">{{ $currentMonth->format('Y/m') }}</div>

        <a class="add-month" href="{{ route('attendanceList.show', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}">
            翌月
        </a>
    </nav>

    <section class="table-wrapper">
        <table class="table">
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>

            @foreach ($days as $day)
                <tr>
                    <td>{{ $day['weekday'] }}</td>
                    <td>{{ $day['clock_in'] }}</td>
                    <td>{{ $day['clock_out'] }}</td>
                    <td>{{ $day['break'] }}</td>
                    <td>{{ $day['total'] }}</td>

                    <td>
                        <a href="{{ route('attendanceDetail.show', [
                            'id' => $day['record_id'] ?? 0,
                            'date' => $day['raw_date']  // → コントローラで日付を渡す
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
