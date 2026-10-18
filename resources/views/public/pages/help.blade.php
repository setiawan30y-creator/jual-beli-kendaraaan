@extends('public.pages._page', ['title' => 'Pusat bantuan'])
@section('page')
@foreach ([
    ['Apakah memasang iklan gratis?', 'Penjual perorangan dapat memasang iklan gratis dengan batas jumlah iklan aktif. Dealer memilih paket sesuai kebutuhan.'],
    ['Berapa lama iklan direview?', 'Moderator meninjau iklan pada jam kerja. Anda akan melihat status dan alasan bila iklan ditolak.'],
    ['Apakah platform menerima pembayaran?', 'Saat ini transaksi dilakukan langsung antara pembeli dan penjual. Platform tidak memegang dana pembeli.'],
    ['Bagaimana data dokumen saya dilindungi?', 'Dokumen dan nomor identitas kendaraan disimpan privat, akses dibatasi sesuai izin dan setiap akses dicatat.'],
    ['Bagaimana memasang aplikasi?', 'Gunakan tombol “Instal Aplikasi” di website. Jika browser tidak mendukung, ikuti panduan “Tambahkan ke layar utama”.'],
] as [$q, $a])
    <details><summary style="cursor:pointer;font-weight:700;padding:6px 0">{{ $q }}</summary><p class="muted">{{ $a }}</p></details>
@endforeach
<p class="mb-0">Butuh bantuan lain? Email <a href="mailto:{{ config('marketplace.support_email') }}">{{ config('marketplace.support_email') }}</a>.</p>
@endsection
