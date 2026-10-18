@extends('layouts.app', ['pageTitle' => $title])
@section('content')
<div class="container section" style="max-width:820px">
    <nav class="breadcrumb"><a href="{{ route('home') }}">Beranda</a><span>›</span><span>{{ $title }}</span></nav>
    <h1>{{ $title }}</h1>
    <div class="card card-body stack">@yield('page')</div>
</div>
@endsection
