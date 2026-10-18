@extends('public.pages._page', ['title' => 'Simulasi kredit'])
@section('page')
<div class="notice notice-warning"><x-icon name="info" /><div><strong>Hanya estimasi.</strong> Perhitungan memakai asumsi bunga flat yang Anda isi sendiri, bukan penawaran atau persetujuan kredit. Angsuran, biaya asuransi/administrasi, dan keputusan kredit final ditentukan oleh perusahaan pembiayaan.</div></div>
<div class="stack" data-credit>
    <div class="form-grid cols-2">
        <x-field name="price" label="Harga kendaraan (Rp)" type="number" :value="request('harga', 25000000)" min="0" step="100000" />
        <div class="field"><label for="f_dp">Uang muka: <span data-out="dpLabel">20%</span></label><input id="f_dp" type="range" name="dp" min="10" max="60" step="5" value="20" style="width:100%;accent-color:var(--primary);min-height:44px"></div>
        <x-field name="tenor" label="Tenor (bulan)" type="select">
            @foreach ([12, 18, 24, 30, 36, 48] as $t)<option value="{{ $t }}" @selected($t === 24)>{{ $t }} bulan</option>@endforeach
        </x-field>
        <x-field name="rate" label="Asumsi bunga flat per tahun (%)" type="number" value="10" min="0" max="50" step="0.1" hint="Isi sesuai informasi dari perusahaan pembiayaan." />
        <x-field name="admin" label="Asumsi biaya administrasi (Rp)" type="number" value="1500000" min="0" step="50000" class="span-2" />
    </div>
    <div class="stat-grid" aria-live="polite">
        <div class="card stat"><div class="label">Uang muka</div><div class="value" data-out="dp">-</div></div>
        <div class="card stat"><div class="label">Pokok pembiayaan</div><div class="value" data-out="principal">-</div></div>
        <div class="card stat"><div class="label">Estimasi angsuran/bulan</div><div class="value" data-out="monthly" style="color:var(--primary)">-</div></div>
        <div class="card stat"><div class="label">Estimasi total bayar</div><div class="value" data-out="total">-</div></div>
    </div>
</div>
@endsection
