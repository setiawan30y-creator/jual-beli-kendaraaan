@extends('layouts.app', ['pageTitle' => 'Daftar', 'noindex' => true])
@section('content')
<div class="container section" style="max-width:480px">
    <div class="card card-body stack">
        <h1 class="mb-0">Buat akun</h1>
        <form method="post" action="{{ route('register') }}" class="stack">
            @csrf
            <x-field name="name" label="Nama lengkap" required autocomplete="name" placeholder="Nama lengkap" />
            <x-field name="email" label="Email" type="email" required autocomplete="email" />
            <x-field name="phone" label="Nomor telepon/WhatsApp (opsional)" type="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" />
            <x-field name="password" label="Kata sandi" type="password" required autocomplete="new-password" hint="Minimal 8 karakter, berisi huruf dan angka." />
            <x-field name="password_confirmation" label="Ulangi kata sandi" type="password" required autocomplete="new-password" />
            <label class="check"><input type="checkbox" name="terms" value="1" required> <span>Saya menyetujui <a href="{{ route('pages.terms') }}" target="_blank">syarat & ketentuan</a> dan <a href="{{ route('pages.privacy') }}" target="_blank">kebijakan privasi</a>.</span></label>
            <button class="btn btn-primary btn-block">Daftar</button>
        </form>
        <p class="small muted mb-0">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </div>
</div>
@endsection
