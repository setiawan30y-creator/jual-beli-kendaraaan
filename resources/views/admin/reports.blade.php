@extends('layouts.admin')
@section('heading', 'Laporan iklan')
@section('content')
<h1>Laporan iklan</h1>
<div class="chip-row mb-2">@foreach (['open' => 'Terbuka', 'reviewed' => 'Ditindak', 'dismissed' => 'Diabaikan'] as $k => $v)<a class="chip {{ $status === $k ? 'is-active' : '' }}" href="{{ route('admin.reports', ['status' => $k]) }}">{{ $v }}</a>@endforeach</div>
@if ($reports->isEmpty())
    <div class="card"><x-empty icon="flag" title="Tidak ada laporan" /></div>
@else
<div class="stack">
    @foreach ($reports as $r)
        <div class="card card-body">
            <div class="row-between"><span><strong>{{ \App\Models\ListingReport::REASONS[$r->reason] ?? $r->reason }}</strong> — <a href="{{ route('listings.show', $r->listing) }}" target="_blank">{{ $r->listing->title }}</a></span><span class="small muted">{{ $r->created_at->diffForHumans() }} · {{ $r->reporter?->name ?? 'Tamu' }}</span></div>
            @if ($r->description)<p class="mt-1 mb-0">{{ $r->description }}</p>@endif
            @if ($r->status === 'open')
                <form method="post" action="{{ route('admin.reports.update', $r) }}" class="row mt-2">@csrf @method('patch')
                    <input class="input" style="flex:1;min-width:200px" name="note" required placeholder="Catatan tindakan" aria-label="Catatan tindakan">
                    <label class="check small"><input type="checkbox" name="archive_listing" value="1"> <span>Turunkan iklan</span></label>
                    <button class="btn btn-primary btn-sm" name="status" value="reviewed">Tindak</button>
                    <button class="btn btn-outline btn-sm" name="status" value="dismissed">Abaikan</button>
                </form>
            @endif
        </div>
    @endforeach
</div>
{{ $reports->links() }}
@endif
@endsection
