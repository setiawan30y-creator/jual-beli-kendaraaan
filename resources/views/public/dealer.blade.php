@extends('layouts.app', ['pageTitle' => $dealer->name, 'canonical' => route('dealers.show', $dealer), 'metaDescription' => \Illuminate\Support\Str::limit($dealer->description ?: 'Iklan kendaraan dari '.$dealer->name, 155)])
@section('content')
<div style="background:var(--primary);color:var(--on-primary)">
    <div class="container" style="padding:28px 16px">
        <div class="row">
            <span class="avatar" style="width:64px;height:64px;font-size:1.5rem;background:var(--surface);color:var(--primary)">{{ mb_substr($dealer->name, 0, 1) }}</span>
            <div>
                <h1 class="mb-0">{{ $dealer->name }}</h1>
                <div class="row small" style="gap:8px">
                    @if ($dealer->isVerified())<span class="badge badge-success"><x-icon name="verified" />Dealer terverifikasi</span>@else<span class="badge">Belum terverifikasi</span>@endif
                    <span><x-icon name="pin" class="icon icon-sm" style="vertical-align:-3px" /> {{ $dealer->city?->name }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container section">
    <div class="detail">
        <div>
            <div class="row mb-2">
                <a class="chip {{ ! $kondisi ? 'is-active' : '' }}" href="{{ route('dealers.show', $dealer) }}">Semua</a>
                <a class="chip {{ $kondisi === 'new' ? 'is-active' : '' }}" href="{{ route('dealers.show', [$dealer, 'kondisi' => 'new']) }}">Baru</a>
                <a class="chip {{ $kondisi === 'used' ? 'is-active' : '' }}" href="{{ route('dealers.show', [$dealer, 'kondisi' => 'used']) }}">Seken</a>
            </div>
            @if ($listings->isEmpty())
                <div class="card"><x-empty icon="moto" title="Belum ada iklan tayang" /></div>
            @else
                <div class="grid grid-cards">@foreach ($listings as $l)<x-listing-card :listing="$l" />@endforeach</div>
                {{ $listings->links() }}
            @endif
        </div>
        <aside class="detail-aside stack">
            <div class="card card-body">
                <h2 class="card-title">Tentang dealer</h2>
                <p class="muted">{{ $dealer->description ?: 'Dealer belum menambahkan deskripsi.' }}</p>
                @if ($dealer->whatsapp || $dealer->phone)
                    @php $wa = preg_replace('/\D/', '', $dealer->whatsapp ?: $dealer->phone); $wa = str_starts_with($wa, '0') ? '62'.substr($wa, 1) : $wa; @endphp
                    <a class="btn btn-success btn-block" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><x-icon name="phone" />Hubungi via WhatsApp</a>
                @endif
            </div>
            <div class="card card-body">
                <h2 class="card-title">Cabang</h2>
                <ul class="checklist">
                    @foreach ($dealer->branches as $b)
                        <li><x-icon name="pin" class="icon icon-sm" /><span><strong>{{ $b->name }}</strong><br><span class="small muted">{{ $b->address }}{{ $b->city ? ', '.$b->city->name : '' }}</span></span></li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
