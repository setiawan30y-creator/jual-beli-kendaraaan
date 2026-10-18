@extends('public.pages._page', ['title' => 'Kebijakan privasi'])
@section('page')
<div class="notice notice-warning"><x-icon name="info" /><div>Draf templat. Tinjau bersama penasihat hukum agar sesuai UU Pelindungan Data Pribadi dan ketentuan yang berlaku sebelum diluncurkan.</div></div>
<h3>Data yang kami kumpulkan</h3><p>Data akun (nama, email, telepon), data iklan, foto kendaraan, dokumen verifikasi kendaraan, serta data minat/jadwal yang Anda kirim.</p>
<h3>Penggunaan</h3><p>Menampilkan iklan, menghubungkan pembeli dan penjual atas persetujuan Anda, verifikasi, pencegahan penipuan, dan peningkatan layanan.</p>
<h3>Perlindungan</h3><p>Dokumen kendaraan dan nomor identitas disimpan privat; nomor rangka/mesin dienkripsi. Akses dibatasi berdasarkan peran dan dicatat dalam log audit.</p>
<h3>Hak Anda</h3><p class="mb-0">Anda dapat meminta akses, koreksi, atau penghapusan data sesuai ketentuan hukum melalui <a href="mailto:{{ config('marketplace.support_email') }}">{{ config('marketplace.support_email') }}</a>.</p>
@endsection
