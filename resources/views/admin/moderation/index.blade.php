@extends('layouts.admin')
@section('heading', 'Moderasi iklan')
@section('content')
<h1>Antrean moderasi</h1>
@if ($listings->isEmpty())
    <div class="card"><x-empty icon="shield" title="Antrean kosong">Tidak ada iklan yang menunggu review.</x-empty></div>
@else
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Iklan</th><th>Penjual</th><th class="num">Harga</th><th>Foto publik</th><th>Diajukan</th><th></th></tr></thead>
    <tbody>
    @foreach ($listings as $l)
        <tr>
            <td data-label="Iklan"><strong>{{ $l->title }}</strong><br><span class="small muted">{{ ucfirst($l->category) }} · {{ $l->condition === 'new' ? 'Baru' : 'Seken' }} · {{ $l->city?->name }}</span></td>
            <td data-label="Penjual">{{ $l->tenant?->name ?? $l->seller->name.' (perorangan)' }}</td>
            <td data-label="Harga" class="num">{{ \App\Support\Format::rupiah($l->price) }}</td>
            <td data-label="Foto">{{ $l->unit->media->filter->isPublic()->count() }}</td>
            <td data-label="Diajukan">{{ $l->updated_at->diffForHumans() }}</td>
            <td><a class="btn btn-primary btn-sm" href="{{ route('admin.moderation.show', $l) }}">Review</a></td>
        </tr>
    @endforeach
    </tbody></table></div></div>
{{ $listings->links() }}
@endif
@endsection
