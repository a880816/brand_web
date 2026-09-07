@extends('layouts.auth') @section('title', '登入') @section('content')<p class="admin-kicker">
    MANAGEMENT PORTAL</p>
<h1>登入維護後台</h1>
<p>使用你的帳號管理品牌內容。</p>
<form method="post" action="{{ route('login.store') }}" class="admin-form">
    @csrf
    <label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
    </label>
    @error('email')
        <p class="field-error">{{ $message }}</p>
    @enderror
    <label>
        密碼<input type="password" name="password" required autocomplete="current-password">
    </label>
    @error('password')
        <p class="field-error">{{ $message }}</p>
    @enderror
    <label class="check">
        <input type="checkbox" name="remember" value="1"> 保持登入</label>
    <button class="admin-button" type="submit">登入</button>
</form>
<a class="auth-link" href="{{ route('password.request') }}">忘記密碼？</a>@endsection
