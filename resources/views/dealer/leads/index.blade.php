@extends('layouts.dealer')
@section('heading', 'Leads / CRM')
@section('content')
<h1>Leads</h1>
<div class="chip-row mb-2">
    <a class="chip {{ ! $status ? 'is-active' : '' }}" href="{{ route('dealer.leads.index') }}">Semua ({{ $counts->sum() }})</a>
    @foreach (\App\Models\Lead::STATUSES as $k => $v)<a class="chip {{ $status === $k ? 'is-active' : '' }}" href="{{ route('dealer.leads.index', ['status' => $k]) }}">{{ $v }} ({{ $counts[$k] ?? 0 }})</a>@endforeach
</div>
@if ($leads->isEmpty())
    <div class="card"><x-empty icon="chat" title="Belum ada lead">Lead muncul ketika calon pembeli mengirim minat, klik WhatsApp, atau mengajukan jadwal.</x-empty></div>
@else
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Calon pembeli</th><th>Iklan</th><th>Sumber</th><th>Status</th><th>Sales</th><th>Masuk</th></tr></thead>
    <tbody>
    @foreach ($leads as $l)
        <tr>
            <td data-label="Pembeli"><a href="{{ route('dealer.leads.show', $l) }}"><strong>{{ $l->buyer_name }}</strong></a><br><span class="small muted">{{ $l->buyer_phone }}</span></td>
            <td data-label="Iklan">{{ $l->listing?->title }}</td>
            <td data-label="Sumber">{{ ucfirst($l->source) }}</td>
            <td data-label="Status"><x-status-badge :status="$l->status" :label="\App\Models\Lead::STATUSES[$l->status]" />
                @if ($l->next_follow_up_at && $l->next_follow_up_at->isPast() && ! in_array($l->status, ['menang', 'tidak_lanjut']))<span class="badge badge-danger">Follow-up lewat</span>@endif</td>
            <td data-label="Sales">{{ $l->assignee?->name ?? '—' }}</td>
            <td data-label="Masuk">{{ $l->created_at->diffForHumans() }}</td>
        </tr>
    @endforeach
    </tbody></table></div></div>
{{ $leads->links() }}
@endif
@endsection
