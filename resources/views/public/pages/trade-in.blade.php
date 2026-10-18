@extends('public.pages._page', ['title' => 'Tukar tambah'])
@section('page')
<p>Ingin mengganti kendaraan lama? Banyak dealer di {{ $brandName }} menerima tukar tambah: kendaraan lama Anda dinilai, lalu nilainya mengurangi harga kendaraan yang Anda beli.</p>
<ol>
    <li>Pilih kendaraan incaran dari dealer yang melayani tukar tambah.</li>
    <li>Kirim minat dan sebutkan kendaraan lama Anda (merek, tahun, kilometer, kondisi).</li>
    <li>Dealer melakukan inspeksi langsung dan memberi penawaran.</li>
    <li>Selisih harga dibayar sesuai kesepakatan.</li>
</ol>
<div class="notice notice-info"><x-icon name="info" /><div>Nilai estimasi tukar tambah <strong>bukan harga final</strong> sebelum inspeksi fisik dan pemeriksaan dokumen dilakukan dealer.</div></div>
<a class="btn btn-primary" href="{{ route('catalog', ['penjual' => 'dealer']) }}">Cari kendaraan dari dealer</a>
@endsection
