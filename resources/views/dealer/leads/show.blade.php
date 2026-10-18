@extends('layouts.dealer')
@section('heading', 'Detail lead')
@section('content')
<nav class="breadcrumb"><a href="{{ route('dealer.leads.index') }}">Leads</a><span>›</span><span>#{{ $lead->id }}</span></nav>
<div class="detail">
    <div class="stack">
        <div class="card card-body">
            <div class="row-between"><h1 class="mb-0">{{ $lead->buyer_name }}</h1><x-status-badge :status="$lead->status" :label="\App\Models\Lead::STATUSES[$lead->status]" /></div>
            <dl class="kv mt-2">
                <dt>Telepon</dt><dd><a href="tel:{{ $lead->buyer_phone }}">{{ $lead->buyer_phone }}</a></dd>
                <dt>Iklan</dt><dd>{{ $lead->listing?->title }}</dd>
                <dt>Sumber</dt><dd>{{ ucfirst($lead->source) }}</dd>
                <dt>Persetujuan kontak</dt><dd>{{ $lead->contact_consent ? 'Ya' : 'Tidak' }}</dd>
                <dt>Masuk</dt><dd>{{ $lead->created_at->format('d/m/Y H:i') }}</dd>
            </dl>
            @if ($lead->message)<p class="mt-2 mb-0" style="white-space:pre-line">“{{ $lead->message }}”</p>@endif
            @php $wa = preg_replace('/\D/', '', $lead->buyer_phone); $wa = str_starts_with($wa, '0') ? '62'.substr($wa, 1) : $wa; @endphp
            <div class="row mt-2">
                @if ($lead->party_id)
                    <a class="btn btn-outline btn-sm" href="{{ route('dealer.parties.show', $lead->party_id) }}"><x-icon name="user" class="icon icon-sm" />Lihat data nasabah</a>
                @elseif (app(\App\Support\TenantContext::class)->can('parties.manage'))
                    <form method="post" action="{{ route('dealer.leads.to-party', $lead) }}">@csrf<button class="btn btn-primary btn-sm"><x-icon name="add" class="icon icon-sm" />Jadikan nasabah</button></form>
                @endif
                <a class="btn btn-success btn-sm" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><x-icon name="phone" class="icon icon-sm" />WhatsApp</a><a class="btn btn-outline btn-sm" href="tel:{{ $lead->buyer_phone }}">Telepon</a></div>
        </div>
        <div class="card card-body">
            <h2 class="card-title">Aktivitas</h2>
            <ul class="timeline small">
                @foreach ($lead->activities as $a)
                    <li><strong>{{ ucfirst($a->activity_type) }}</strong> · {{ $a->actor?->name ?? 'Sistem' }} · <span class="muted">{{ $a->occurred_at?->format('d/m/Y H:i') }}</span><br><span style="white-space:pre-line">{{ $a->notes }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>
    <aside class="detail-aside">
        <form class="card card-body stack" method="post" action="{{ route('dealer.leads.update', $lead) }}">
            @csrf @method('patch')
            <h2 class="card-title mb-0">Tindak lanjut</h2>
            <x-field name="status" label="Status pipeline" type="select">
                @foreach (\App\Models\Lead::STATUSES as $k => $v)<option value="{{ $k }}" @selected($lead->status === $k)>{{ $v }}</option>@endforeach
            </x-field>
            @if ($canAssign)
                <x-field name="assigned_user_id" label="Ditugaskan ke" type="select">
                    <option value="">— Belum ditugaskan —</option>
                    @foreach ($staff as $s)<option value="{{ $s->user_id }}" @selected($lead->assigned_user_id === $s->user_id)>{{ $s->user->name }} ({{ $s->roleLabel() }})</option>@endforeach
                </x-field>
            @endif
            <x-field name="next_follow_up_at" label="Jadwal follow-up" type="datetime-local" :value="$lead->next_follow_up_at?->format('Y-m-d\TH:i')" />
            <x-field name="note" label="Catatan internal" type="textarea" rows="3" />
            <button class="btn btn-primary">Simpan</button>
        </form>
    </aside>
</div>
@endsection
