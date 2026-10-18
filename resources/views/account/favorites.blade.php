@extends('layouts.app', ['pageTitle' => 'Favorit', 'noindex' => true])
@section('content')
<div class="container section">
    <h1>Favorit</h1>
    @include('account._nav')
    @if ($listings->isEmpty())
        <div class="card"><x-empty icon="heart" title="Belum ada favorit">Ketuk ikon hati pada iklan untuk menyimpannya. <a href="{{ route('catalog') }}">Jelajahi kendaraan</a></x-empty></div>
    @else
        <div class="grid grid-cards cols-4">@foreach ($listings as $l)<x-listing-card :listing="$l" :favorite="true" />@endforeach</div>
        {{ $listings->links() }}
    @endif
</div>
@endsection
