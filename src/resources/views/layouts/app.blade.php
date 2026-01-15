<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>coachtech勤怠管理アプリ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('css')
</head>
<body>
    <header class="layout-header">
        <div class="layout-header-container">
            <div class="layout-header-logo">
                <img src="{{ asset('img/COACHTECHヘッダーロゴ.png') }}" alt="coachtechロゴ">
            </div>

            {{-- 「URLが /login でも /register でも /email/verify* でもない場合」 という条件 --}}
            @if (!Request::is('login') && !Request::is('register') && !Request::is('email/verify*'))
                <nav class="layout-header-menu">
                @auth
                    <ul class="layout-header-menu-list">
                        @if (Auth::user()->role === 'admin')
                            <li><a href="{{ route('admin.attendanceList.show') }}">勤怠一覧</a></li>
                            <li><a href="{{ route('staffList.show') }}">スタッフ一覧</a></li>
                            <li><a href="{{ route('applicationList.show') }}">申請一覧</a></li>
                            <li class="layout-header-logout">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="layout-header-button">ログアウト</button>
                                </form>
                            </li>
                        @elseif ($status === '退勤済')
                            <li class="layout-header-clock-out"><a href="{{ route('attendanceList.show') }}">今月の出勤一覧</a></li>
                            <li class="layout-header-clock-out"><a href="{{ route('applicationList.show') }}">申請一覧</a></li>
                            <li class="layout-header-logout">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="layout-header-button">ログアウト</button>
                                </form>
                            </li>
                        @else
                            <li><a href="{{ route('attendance.show') }}">勤怠</a></li>
                            <li><a href="{{ route('attendanceList.show') }}">勤怠一覧</a></li>
                            <li><a href="{{ route('applicationList.show') }}">申請</a></li>
                            <li class="layout-header-logout">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="layout-header-button">ログアウト</button>
                                </form>
                            </li>
                        @endif
                    </ul>
                @endauth
            </nav>
            @endif
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>