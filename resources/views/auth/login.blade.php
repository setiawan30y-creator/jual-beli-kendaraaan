@extends('layouts.app', ['pageTitle' => 'Masuk', 'noindex' => true])
@section('content')
<div class="container section" style="max-width:440px">
    <div class="card card-body stack">
        <h1 class="mb-0">Masuk</h1>
        <p class="muted mb-0">Masuk untuk menyimpan favorit, mengirim jadwal, dan mengelola iklan atau dealer.</p>
        <form method="post" action="{{ route('login') }}" class="stack">
            @csrf
            <x-field name="email" label="Email" type="email" required autocomplete="email" autofocus />
            <x-field name="password" label="Kata sandi" type="password" required autocomplete="current-password" />
            <label class="check"><input type="checkbox" name="remember" value="1"> <span>Ingat saya</span></label>
            <button class="btn btn-primary btn-block">Masuk</button>
        </form>
        <p class="small muted mb-0">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
    </div>
</div>
@endsection
