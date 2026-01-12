@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verification_email.css') }}">
@endsection

@section('content')
<div class="verify-wrapper">
    <h2 class="verify-message">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </h2>

    @if (session('message'))
        <p class="message">{{ session('message') }}</p>
    @endif

    <div class="verify-actions">
        <a href="https://mailtrap.io/inboxes/4273732/messages"  class="verify-btn">
            認証はこちらから
        </a>
        <div class="retry-box">
            <form method="post" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="retry-btn">認証メールを再送する</button>
            </form>
        </div>
    </div>
</div>
@endsection