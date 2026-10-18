@extends('public.pages._page', ['title' => 'Anda sedang offline', 'noindex' => true])
@section('page')
<x-empty icon="cloud" title="Koneksi internet tidak tersedia">Halaman ini belum tersimpan untuk dibuka offline. Data harga dan ketersediaan selalu diperbarui dari server, dan transaksi tidak dapat dilakukan tanpa koneksi.</x-empty>
<button class="btn btn-primary" onclick="location.reload()">Coba lagi</button>
@endsection
