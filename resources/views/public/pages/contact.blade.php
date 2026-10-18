@extends('public.pages._page', ['title' => 'Kontak'])
@section('page')
<p>Hubungi tim {{ $brandName }} untuk pertanyaan akun, kemitraan dealer, atau laporan keamanan.</p>
<dl class="kv"><dt>Email</dt><dd><a href="mailto:{{ config('marketplace.support_email') }}">{{ config('marketplace.support_email') }}</a></dd><dt>Jam layanan</dt><dd>Senin–Jumat, 09.00–17.00 WIB</dd></dl>
@endsection
