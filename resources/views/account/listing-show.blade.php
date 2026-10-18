@extends('layouts.app', ['pageTitle' => 'Kelola iklan', 'noindex' => true])
@section('content')
<div class="container section">
    <nav class="breadcrumb"><a href="{{ route('account.listings') }}">Iklan saya</a><span>›</span><span>{{ $listing->title }}</span></nav>
    <div class="row-between mb-2">
        <div><h1 class="mb-0">{{ $listing->title }}</h1>
            <div class="row small"><x-status-badge :status="$listing->status" :label="\App\Models\Listing::STATUSES[$listing->status]" /><span class="muted">{{ \App\Support\Format::rupiah($listing->price) }} · {{ $listing->views_count }} kali dilihat</span></div></div>
        <div class="row">
            <a class="btn btn-outline btn-sm" href="{{ route('listings.show', $listing) }}" target="_blank">Lihat iklan</a>
            @if (in_array($listing->status, ['draft', 'rejected', 'expired']))
                <form method="post" action="{{ route('account.listings.submit', $listing) }}">@csrf<button class="btn btn-primary btn-sm">Ajukan review</button></form>
            @endif
            @if (in_array($listing->status, ['published', 'reserved']))
                <form method="post" action="{{ route('account.listings.sold', $listing) }}" data-confirm="Tandai kendaraan ini sudah terjual?">@csrf<button class="btn btn-success btn-sm">Tandai terjual</button></form>
            @endif
            @if ($listing->status !== 'archived')
                <form method="post" action="{{ route('account.listings.archive', $listing) }}" data-confirm="Arsipkan iklan ini?">@csrf<button class="btn btn-text btn-sm" style="color:var(--danger)">Arsipkan</button></form>
            @endif
        </div>
    </div>
    @if ($listing->status === 'rejected' && $listing->rejection_reason)
        <div class="notice notice-danger mb-2"><x-icon name="warning" /><div><strong>Ditolak moderator:</strong> {{ $listing->rejection_reason }}. Perbaiki lalu ajukan ulang.</div></div>
    @elseif ($listing->status === 'pending_review')
        <div class="notice notice-info mb-2"><x-icon name="info" /><div>Iklan sedang direview moderator.</div></div>
    @endif

    <div class="card card-body mb-2">
        <h2 class="card-title">Foto & bukti kendaraan</h2>
        <x-media-uploader :unit="$listing->unit" :checklist="$checklist" :can-manage="! in_array($listing->status, ['sold', 'archived'])" :can-private="true" />
    </div>

    <div class="card card-body">
        <h2 class="card-title">Minat masuk ({{ $listing->leads->count() }})</h2>
        @forelse ($listing->leads as $lead)
            <div class="row-between mb-2"><span><strong>{{ $lead->buyer_name }}</strong> · <a href="tel:{{ $lead->buyer_phone }}">{{ $lead->buyer_phone }}</a><br><span class="small muted">{{ $lead->message }}</span></span><span class="small muted">{{ $lead->created_at->diffForHumans() }}</span></div>
        @empty<p class="muted mb-0">Belum ada calon pembeli yang menghubungi.</p>@endforelse
    </div>
</div>
@endsection
