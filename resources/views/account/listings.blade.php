@extends('layouts.app', ['pageTitle' => 'Iklan saya', 'noindex' => true])
@section('content')
<div class="container section">
    <div class="row-between"><h1 class="mb-0">Iklan saya</h1><a class="btn btn-primary btn-sm" href="{{ route('sell.create') }}"><x-icon name="add" class="icon icon-sm" />Pasang iklan</a></div>
    @include('account._nav')
    @if ($listings->isEmpty())
        <div class="card"><x-empty icon="tag" title="Belum ada iklan">Pasang iklan pertama Anda — gratis untuk penjual perorangan.</x-empty></div>
    @else
        <div class="card"><div class="table-wrap"><table class="table stacked">
            <thead><tr><th>Iklan</th><th>Status</th><th class="num">Harga</th><th class="num">Dilihat</th><th></th></tr></thead>
            <tbody>
            @foreach ($listings as $l)
                <tr>
                    <td data-label="Iklan"><a href="{{ route('account.listings.show', $l) }}">{{ $l->title }}</a></td>
                    <td data-label="Status"><x-status-badge :status="$l->status" :label="\App\Models\Listing::STATUSES[$l->status]" /></td>
                    <td data-label="Harga" class="num">{{ \App\Support\Format::rupiah($l->price) }}</td>
                    <td data-label="Dilihat" class="num">{{ $l->views_count }}</td>
                    <td><a class="btn btn-outline btn-sm" href="{{ route('account.listings.show', $l) }}">Kelola</a></td>
                </tr>
            @endforeach
            </tbody></table></div></div>
        {{ $listings->links() }}
    @endif
</div>
@endsection
