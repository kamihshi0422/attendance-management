@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="form-wrapper">
    <div class="form-heading">
        <h1 class="heading-title">ログイン</h1>
    </div>
    <form action="/login" method="post">
        @csrf
        <div class="form-group">
            <div class="form-group-title">
                <span class="form-label-item">メールアドレス</span>
            </div>
            <div class="form-group-content">
                <div class="form-input-text">
                    <input type="text" name="email" value="{{ old('email') }}">
                </div>
                <div class="form-error">
                    <!-- ディレクティブ　 -->
                    @error('email')
                        {{ $message }}
                    @enderror
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="form-group-title">
                <span class="form-label-item">パスワード</span>
            </div>
            <div class="form-group-content">
                <div class="form-input-text">
                    <input type="password" name="password">
                </div>
                <div class="form-error">
                    @error('password')
                        {{ $message }}
                    @enderror
                </div>
            </div>
        </div>

        @if ($errors->has('login'))
            <div class="form-error">
                {{ $errors->first('login') }}
            </div>
        @endif

        <div class="form-button">
            <button class="form-button-submit" type="submit">ログインする</button>
        </div>
    </form>

    <div class="login-link">
        <a class="login-button-submit" href="/register">会員登録はこちら</a>
    </div>
</div>
@endsection