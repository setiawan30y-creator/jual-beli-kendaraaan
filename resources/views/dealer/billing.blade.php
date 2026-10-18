@extends('layouts.dealer')
@section('heading', 'Paket & langganan')
@section('content')
@php $F = \App\Support\Format::class; @endphp
<h1>Paket & langganan</h1>
<div class="grid grid-2 mb-2">
    <div class="card card-body">
        <h2 class="card-title">Langganan aktif</h2>
        @if ($subscription)
            <dl class="kv">
                <dt>Paket</dt><dd>{{ $subscription->plan->name }}</dd>
                <dt>Status</dt><dd><x-status-badge :status="$subscription->status" /></dd>
                <dt>Periode</dt><dd>{{ $subscription->current_period_start?->format('d/m/Y') }} – {{ $subscription->current_period_end?->format('d/m/Y') }}</dd>
                <dt>Harga</dt><dd>{{ $F::rupiah($subscription->plan->price) }}/bulan</dd>
            </dl>
        @else
            <p class="muted mb-0">Belum ada langganan aktif. Hubungi tim platform.</p>
        @endif
    </div>
    <div class="card card-body">
        <h2 class="card-title">Penggunaan kuota</h2>
        @foreach ($usage as $u)
            @php $pct = $u['limit'] ? min(100, round($u['used'] / max($u['limit'], 1) * 100)) : 0; @endphp
            <div class="mb-2"><div class="row-between small"><span>{{ $u['label'] }}</span><span>{{ $u['used'] }} / {{ $u['limit'] ?? '∞' }}</span></div>
                <div class="progress {{ $pct >= 100 ? 'is-danger' : ($pct >= 80 ? 'is-warning' : '') }}"><span style="width:{{ $pct }}%"></span></div>
                @if ($u['limit'] !== null && $u['used'] >= $u['limit'])<span class="small" style="color:var(--danger)">Kuota penuh — aksi yang menambah {{ strtolower($u['label']) }} akan ditolak sampai paket ditingkatkan.</span>@endif</div>
        @endforeach
    </div>
</div>
<h2>Pilihan paket</h2>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
    @foreach ($plans as $p)
        <div class="card card-body stack" @if($subscription?->plan_id === $p->id) style="border-color:var(--primary);box-shadow:0 0 0 2px var(--primary-soft)" @endif>
            <div class="row-between"><h3 class="mb-0">{{ $p->name }}</h3>@if($subscription?->plan_id === $p->id)<span class="badge badge-info">Aktif</span>@endif</div>
            <div class="price-xl" style="font-size:1.4rem">{{ $p->price > 0 ? $F::rupiah($p->price) : 'Gratis' }}<span class="small muted">{{ $p->price > 0 ? '/bulan' : '' }}</span></div>
            <ul class="small" style="padding-left:1rem;margin:0">
                <li>{{ $p->limit('listing_limit') ?? 'Tanpa batas' }} iklan aktif</li>
                <li>{{ $p->limit('staff_limit') ?? 'Tanpa batas' }} anggota staf</li>
                <li>{{ $p->limit('branch_limit') ?? 'Tanpa batas' }} cabang</li>
            </ul>
        </div>
    @endforeach
</div>
<div class="notice notice-info mt-2"><x-icon name="info" /><div>Pembayaran online langganan belum diaktifkan pada versi ini. Untuk upgrade, hubungi <a href="mailto:{{ config('marketplace.support_email') }}">{{ config('marketplace.support_email') }}</a>; perubahan paket dicatat di log audit.</div></div>
@if ($invoices->isNotEmpty())
    <h2 class="mt-3">Invoice</h2>
    <div class="card"><div class="table-wrap"><table class="table stacked"><thead><tr><th>No.</th><th>Periode</th><th>Status</th><th class="num">Total</th></tr></thead><tbody>
        @foreach ($invoices as $inv)<tr><td data-label="No.">{{ $inv->invoice_number }}</td><td data-label="Periode">{{ $inv->period_start?->format('d/m/Y') }} – {{ $inv->period_end?->format('d/m/Y') }}</td><td data-label="Status"><x-status-badge :status="$inv->status" /></td><td data-label="Total" class="num">{{ $F::rupiah($inv->total) }}</td></tr>@endforeach
    </tbody></table></div></div>
@endif
@endsection
