@extends('layouts.dealer')
@section('heading', $unit->display_name)
@section('content')
@php $F = \App\Support\Format::class; $listing = $unit->listing; @endphp
<nav class="breadcrumb"><a href="{{ route('dealer.units.index') }}">Inventaris</a><span>›</span><span>{{ $unit->stock_code ?? '#'.$unit->id }}</span></nav>
<div class="row-between mb-2">
    <div>
        <h1 class="mb-0">{{ $unit->display_name }}</h1>
        <div class="row small"><x-status-badge :status="$unit->status" :label="\App\Models\VehicleUnit::STATUSES[$unit->status]" />
            <span class="badge">{{ $unit->condition === 'new' ? 'Baru' : 'Seken' }}</span>
            @if ($unit->ownership_type === 'consignment')<span class="badge badge-warning">Unit titipan — bukan stok milik dealer</span>@endif</div>
    </div>
    @if ($canManage)<a class="btn btn-outline" href="{{ route('dealer.units.edit', $unit) }}">Ubah data</a>@endif
</div>

<div class="detail">
    <div class="stack">
        <div class="card card-body">
            <h2 class="card-title">Foto & bukti kendaraan</h2>
            <x-media-uploader :unit="$unit" :checklist="$checklist" :can-manage="$canManage" :can-private="$canPrivate" :can-verify="$canVerify" verify-route="dealer.media.verify" />
        </div>

        @if ($canCosts)
        <div class="card card-body">
            <h2 class="card-title">Kartu biaya / HPP unit</h2>
            <div class="table-wrap"><table class="table stacked">
                <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Status</th><th class="num">Nilai</th><th></th></tr></thead>
                <tbody>
                @forelse ($unit->costs->sortBy('entry_date') as $c)
                    <tr>
                        <td data-label="Tanggal">{{ $c->entry_date->format('d/m/Y') }}</td>
                        <td data-label="Kategori">{{ \App\Models\VehicleCostEntry::CATEGORIES[$c->category] ?? $c->category }}</td>
                        <td data-label="Keterangan">{{ $c->description }}@if($c->reference_no)<br><span class="small muted">Ref: {{ $c->reference_no }}</span>@endif</td>
                        <td data-label="Status"><x-status-badge :status="$c->status" :label="['posted' => 'Diposting', 'estimate' => 'Estimasi', 'reversed' => 'Dibalik'][$c->status]" /></td>
                        <td data-label="Nilai" class="num">{{ $F::rupiah($c->amount) }}</td>
                        <td>@if ($c->status !== 'reversed' && ! $c->reverses_id)
                            <details><summary class="small" style="cursor:pointer">{{ $c->status === 'posted' ? 'Reversal' : 'Batalkan' }}</summary>
                                <form method="post" action="{{ route('dealer.units.costs.reverse', [$unit, $c]) }}" class="stack mt-1">@csrf
                                    <input class="input" name="reason" required placeholder="Alasan koreksi" aria-label="Alasan koreksi"><button class="btn btn-outline btn-sm">Proses</button></form></details>
                        @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Belum ada biaya tercatat.</td></tr>
                @endforelse
                </tbody></table></div>
            <details class="mt-2">
                <summary class="btn btn-outline btn-sm">Catat biaya</summary>
                <form method="post" action="{{ route('dealer.units.costs.store', $unit) }}" class="form-grid cols-2 mt-2">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <x-field name="category" label="Kategori" type="select" required>@foreach (\App\Models\VehicleCostEntry::CATEGORIES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</x-field>
                    <x-field name="status" label="Jenis" type="select" required><option value="posted">Aktual (diposting ke HPP)</option><option value="estimate">Estimasi (belum masuk HPP)</option></x-field>
                    <x-field name="description" label="Keterangan" required class="span-2" />
                    <x-field name="amount" label="Nilai (Rp)" type="number" min="1" step="1000" required />
                    <x-field name="entry_date" label="Tanggal" type="date" :value="now()->toDateString()" required />
                    <x-field name="reference_no" label="No. referensi (opsional)" class="span-2" />
                    <div class="span-2"><button class="btn btn-primary">Simpan biaya</button></div>
                </form>
            </details>
        </div>
        @endif

        <div class="card card-body">
            <h2 class="card-title">Riwayat aktivitas</h2>
            <ul class="timeline small">
                @forelse ($audit as $a)
                    <li><strong>{{ $a->action }}</strong> · {{ $a->actor?->name ?? 'Sistem' }} · <span class="muted">{{ $a->created_at->format('d/m/Y H:i') }}</span>
                        @if ($a->after_json)<br><span class="muted">{{ collect($a->after_json)->map(fn ($v, $k) => $k.': '.(is_scalar($v) ? $v : json_encode($v)))->implode(', ') }}</span>@endif
                        @if ($a->reason)<br><span class="muted">Alasan: {{ $a->reason }}</span>@endif</li>
                @empty<li class="muted">Belum ada aktivitas.</li>@endforelse
            </ul>
        </div>
    </div>

    <aside class="detail-aside stack">
        @if ($canCosts)
        <div class="card card-body">
            <h2 class="card-title">Ringkasan HPP & laba</h2>
            <dl class="kv">
                @foreach ($summary['by_category'] as $cat => $amt)<dt>{{ \App\Models\VehicleCostEntry::CATEGORIES[$cat] ?? $cat }}</dt><dd class="num">{{ $F::rupiah($amt) }}</dd>@endforeach
                <dt><strong>Total HPP aktual</strong></dt><dd><strong>{{ $F::rupiah($summary['hpp']) }}</strong></dd>
                <dt>Estimasi belum diposting</dt><dd style="color:var(--warning)">{{ $F::rupiah($summary['estimate']) }}</dd>
                <dt>Harga jual target</dt><dd>{{ $F::rupiah($summary['price']) }}</dd>
                <dt>Estimasi laba kotor</dt><dd style="color:{{ $summary['gross_profit'] >= 0 ? 'var(--success)' : 'var(--danger)' }}">{{ $F::rupiah($summary['gross_profit']) }}</dd>
                <dt>Margin</dt><dd>{{ $summary['margin'] !== null ? $summary['margin'].'%' : '—' }}</dd>
            </dl>
            <p class="small muted mt-1 mb-0">Laba kotor = harga jual − HPP aktual yang diposting. Estimasi ditampilkan terpisah.</p>
        </div>
        @endif

        <div class="card card-body">
            <h2 class="card-title">Data unit</h2>
            <dl class="kv">
                <dt>Kode stok</dt><dd>{{ $unit->stock_code ?? '—' }}</dd>
                <dt>Cabang</dt><dd>{{ $unit->branch?->name ?? '—' }}</dd>
                <dt>Kilometer</dt><dd>{{ $F::km($unit->odometer_km) }}</dd>
                <dt>No. rangka</dt><dd>{{ $canPrivate ? ($unit->frameNumber() ?? '—') : \App\Models\VehicleUnit::mask($unit->frameNumber()) }}</dd>
                <dt>No. mesin</dt><dd>{{ $canPrivate ? ($unit->engineNumber() ?? '—') : \App\Models\VehicleUnit::mask($unit->engineNumber()) }}</dd>
                <dt>Dokumen</dt><dd>{{ $unit->registration_status ? str_replace('_', ' ', $unit->registration_status) : '—' }}</dd>
                <dt>Harga jual</dt><dd>{{ $F::rupiah($unit->asking_price) }}</dd>
            </dl>
            @if ($canManage && \App\Models\VehicleUnit::TRANSITIONS[$unit->status])
                <form method="post" action="{{ route('dealer.units.status', $unit) }}" class="stack mt-2">
                    @csrf
                    <x-field name="status" label="Ubah status stok" type="select">
                        @foreach (\App\Models\VehicleUnit::TRANSITIONS[$unit->status] as $s)<option value="{{ $s }}">{{ \App\Models\VehicleUnit::STATUSES[$s] }}</option>@endforeach
                    </x-field>
                    <x-field name="reason" label="Alasan (opsional)" />
                    <button class="btn btn-outline btn-sm">Perbarui status</button>
                </form>
            @endif
        </div>

        <div class="card card-body">
            <h2 class="card-title">Iklan</h2>
            @if ($listing && ! in_array($listing->status, ['archived', 'sold', 'expired']))
                <div class="row-between"><a href="{{ route('listings.show', $listing) }}" target="_blank">{{ $listing->title }}</a><x-status-badge :status="$listing->status" :label="\App\Models\Listing::STATUSES[$listing->status]" /></div>
                @if ($listing->rejection_reason && $listing->status === 'rejected')<p class="small" style="color:var(--danger)">Ditolak: {{ $listing->rejection_reason }}</p>@endif
                <div class="row mt-2">
                    @if (in_array($listing->status, ['draft', 'rejected']))
                        <form method="post" action="{{ route('dealer.listings.submit', $listing) }}">@csrf<button class="btn btn-primary btn-sm">Ajukan review</button></form>
                    @endif
                    @if (in_array($listing->status, ['published', 'reserved']))
                        <form method="post" action="{{ route('dealer.listings.sold', $listing) }}" data-confirm="Tandai iklan dan unit terjual?">@csrf<button class="btn btn-success btn-sm">Tandai terjual</button></form>
                    @endif
                </div>
            @elseif ($canManage || app(\App\Support\TenantContext::class)->can('listings.manage'))
                <form method="post" action="{{ route('dealer.listings.store', $unit) }}" class="stack">
                    @csrf
                    <x-field name="title" label="Judul iklan" required :value="$unit->display_name" maxlength="120" />
                    <x-field name="price" label="Harga iklan (Rp)" type="number" required min="100000" :value="(int) $unit->asking_price" />
                    <x-field name="description" label="Deskripsi" type="textarea" rows="4" />
                    <label class="check"><input type="checkbox" name="negotiable" value="1" checked> <span>Harga dapat dinegosiasikan</span></label>
                    <button class="btn btn-primary btn-sm">Buat draf iklan</button>
                </form>
                <p class="small muted mb-0">Iklan dapat diajukan setelah unit berstatus Tersedia dan memiliki minimal satu foto publik.</p>
            @else
                <p class="muted mb-0">Belum ada iklan.</p>
            @endif
        </div>
    </aside>
</div>
@endsection
