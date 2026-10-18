@extends('layouts.admin')
@section('heading', 'Paket & limit')
@section('content')
<h1>Paket & limit</h1>
<p class="muted">Harga dan limit dapat diubah tanpa deploy. Kosongkan limit untuk “tanpa batas”. Perubahan tercatat di audit.</p>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
    @foreach ($plans as $p)
        <form method="post" action="{{ route('admin.plans.update', $p) }}" class="card card-body stack">@csrf @method('put')
            <div class="row-between"><strong>{{ $p->code }}</strong><span class="small muted">{{ $p->subscriptions_count }} tenant aktif</span></div>
            <x-field name="name" label="Nama" :value="$p->name" required />
            <x-field name="price" label="Harga/bulan (Rp)" type="number" min="0" :value="(int) $p->price" required />
            <x-field name="listing_limit" label="Limit iklan aktif" type="number" min="0" :value="$p->limit('listing_limit')" />
            <x-field name="staff_limit" label="Limit staf" type="number" min="1" :value="$p->limit('staff_limit')" />
            <x-field name="branch_limit" label="Limit cabang" type="number" min="1" :value="$p->limit('branch_limit')" />
            <x-field name="status" label="Status" type="select"><option value="active" @selected($p->status === 'active')>Aktif</option><option value="inactive" @selected($p->status === 'inactive')>Nonaktif</option></x-field>
            <button class="btn btn-primary btn-sm">Simpan</button>
        </form>
    @endforeach
</div>
@endsection
