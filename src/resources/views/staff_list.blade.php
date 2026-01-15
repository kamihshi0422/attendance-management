@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_attendance.css') }}">
@endsection

@section('content')
<div class="wrapper">
    <div class="ttl-box">
        <h1 class="ttl">スタッフ一覧</h1>
    </div>

    <section class="table-wrapper">
        <table class="table">
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>

            @foreach ($staffs as $staff)
                <tr>
                    <td>{{ $staff->name }}</td>
                    <td>{{ $staff->email }}</td>
                    <td>
                        <a href="{{ route('staffAttendance.show', ['id' => $staff->id]) }}">
                            詳細
                        </a>
                    </td>
                </tr>
            @endforeach
        </table>
    </section>
</div>
@endsection
