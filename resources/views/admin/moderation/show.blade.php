@extends('layouts.admin')
@section('heading', 'Review iklan')
@section('content')
@php $u = $listing->unit; @endphp
<nav class="breadcrumb"><a href="{{ route('admin.moderation') }}">Moderasi</a><span>›</span><span>#{{ $listing->id }}</span></nav>
<div class="detail">
    <div class="stack">
        <div class="card card-body">
            <h1 class="mb-0">{{ $listing->title }}</h1>
            <div class="price-xl">{{ \App\Support\Format::rupiah($listing->price) }}</div>
            <dl class="kv mt-2">
                <dt>Model</dt><dd>{{ $u->vehicleModel->fullName() }} ({{ ucfirst($listing->category) }})</dd>
                <dt>Tahun / km</dt><dd>{{ $u->year }} / {{ \App\Support\Format::km($u->odometer_km) }}</dd>
                <dt>Penjual</dt><dd>{{ $listing->tenant?->name ?? $listing->seller->name.' (perorangan)' }}</dd>
                <dt>Status dealer</dt><dd>{{ $listing->tenant ? $listing->tenant->verification_status : '—' }}</dd>
                <dt>No. rangka (tersamar)</dt><dd>{{ \App\Models\VehicleUnit::mask($u->frameNumber()) }}</dd>
            </dl>
            <p class="mt-2" style="white-space:pre-line">{{ $listing->description }}</p>
        </div>
        <div class="card card-body">
            <h2 class="card-title">Foto & bukti</h2>
            <p class="small muted">Membuka dokumen privat tercatat di log audit.</p>
            <x-media-uploader :unit="$u" :checklist="$checklist" :can-private="true" />
        </div>
    </div>
    <aside class="detail-aside stack">
        <form method="post" action="{{ route('admin.moderation.approve', $listing) }}" class="card card-body stack">@csrf
            <h2 class="card-title mb-0">Setujui</h2>
            <p class="small muted mb-0">Iklan tayang selama {{ config('marketplace.listing_ttl_days') }} hari.</p>
            <button class="btn btn-success">Setujui & tayangkan</button>
        </form>
        <form method="post" action="{{ route('admin.moderation.reject', $listing) }}" class="card card-body stack">@csrf
            <h2 class="card-title mb-0">Tolak</h2>
            <x-field name="reason" label="Alasan (dikirim ke penjual)" type="textarea" rows="3" required />
            <button class="btn btn-danger">Tolak iklan</button>
        </form>
    </aside>
</div>
@endsection
