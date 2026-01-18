@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/application_list.css') }}">
@endsection

@section('content')
<section class="wrapper">

    <header class="ttl-box">
        <h1 class="ttl">申請一覧</h1>
    </header>

    <nav class="nav-wrapper">
        <a href="{{ route('applicationList.show', ['status' => 'pending']) }}"
        class="{{ request('status', 'pending') === 'pending' ? 'active' : '' }}">
            承認待ち
        </a>

        <a href="{{ route('applicationList.show', ['status' => 'approved']) }}"
        class="{{ request('status') === 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </nav>

    <section class="table-wrapper">
        <table class="table">
            <tr>
                <th>状態</th>
                <th>名前</th>
                <th>対象日時</th>
                <th>申請理由</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>

            @foreach ($application_list as $applicationItem)
                <tr>
                    <td class="wide-text">{{ $applicationItem->status }}</td>
                    <td class="wide-text">{{ $applicationItem->user->name }}</td>
                    <td class="date-text">{{ \Carbon\Carbon::parse($applicationItem->corrected_clock_in)->format('Y/m/d') }}</td>
                    <td class="wide-text">{{ $applicationItem->reason }}</td>
                    <td class="date-text">{{ $applicationItem->created_at->format('Y/m/d') }}</td>
                    <td class="wide-text">
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('applicationApproval.show', [
                                'attendance_correct_request_id' => $applicationItem->id
                            ]) }}">
                                詳細
                            </a>
                        @else
                            <a href="{{ route('attendanceDetail.show', [
                                'id'   => $applicationItem->attendance_id,
                                'date' => $applicationItem->attendance->work_date
                            ]) }}">
                                詳細
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </section>

</section>
@endsection
