@extends('layouts.app', ['pageTitle' => 'Pesan & jadwal', 'noindex' => true])
@section('content')
<div class="container section">
    <h1>Pesan & jadwal</h1>
    @include('account._nav')
    <div class="grid grid-2">
        <div class="card card-body">
            <h2 class="card-title">Minat masuk ke iklan saya</h2>
            @forelse ($incoming as $l)
                <div class="mb-2"><strong>{{ $l->buyer_name }}</strong> · <a href="tel:{{ $l->buyer_phone }}">{{ $l->buyer_phone }}</a> <span class="small muted">· {{ $l->created_at->diffForHumans() }}</span><br>
                    <span class="small">{{ $l->listing?->title }}</span><br><span class="small muted">{{ $l->message }}</span></div>
            @empty<p class="muted mb-0">Belum ada minat masuk.</p>@endforelse
            {{ $incoming->links() }}
        </div>
        <div class="stack">
            <div class="card card-body">
                <h2 class="card-title">Minat yang saya kirim</h2>
                @forelse ($outgoing as $l)
                    <div class="row-between small mb-2"><a href="{{ $l->listing ? route('listings.show', $l->listing) : '#' }}">{{ $l->listing?->title }}</a><x-status-badge :status="$l->status" :label="\App\Models\Lead::STATUSES[$l->status]" /></div>
                @empty<p class="muted mb-0">Belum ada.</p>@endforelse
            </div>
            <div class="card card-body">
                <h2 class="card-title">Jadwal kunjungan</h2>
                @forelse ($bookings as $b)
                    <div class="row-between small mb-2"><span>{{ $b->listing?->title }}<br><span class="muted">{{ \App\Models\Booking::TYPES[$b->booking_type] }} · {{ $b->starts_at->format('d/m/Y H:i') }}</span></span><x-status-badge :status="$b->status" :label="\App\Models\Booking::STATUSES[$b->status]" /></div>
                @empty<p class="muted mb-0">Belum ada jadwal.</p>@endforelse
            </div>
        </div>
    </div>
</div>
@endsection
