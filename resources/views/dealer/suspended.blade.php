@extends('layouts.dealer')
@section('heading', 'Dealer ditangguhkan')
@section('content')
<div class="card card-body stack" style="max-width:640px">
    <div class="notice notice-danger"><x-icon name="warning" /><div><strong>Akun dealer {{ $tenant->name }} sedang ditangguhkan.</strong><br>{{ $tenant->status_reason ?: 'Hubungi tim bantuan untuk informasi lebih lanjut.' }}</div></div>
    <p>Data Anda tetap tersimpan dan tidak dihapus. Selama penangguhan, Anda masih dapat melihat ringkasan dan informasi langganan.</p>
    <div class="row"><a class="btn btn-primary" href="mailto:{{ config('marketplace.support_email') }}">Hubungi bantuan</a><a class="btn btn-outline" href="{{ route('dealer.billing') }}">Lihat langganan</a></div>
</div>
@endsection
