@extends('layouts.admin')
@section('heading', 'Dashboard platform')
@section('content')
<h1>Dashboard platform</h1>
<div class="stat-grid mb-2">
    @foreach ($stats as $label => $value)<div class="card stat"><div class="label">{{ $label }}</div><div class="value">{{ number_format($value, 0, ',', '.') }}</div></div>@endforeach
</div>
<div class="card stat mb-2" style="max-width:360px"><div class="label">Estimasi MRR (langganan bulanan aktif)</div><div class="value">{{ \App\Support\Format::rupiah($mrr) }}</div></div>
<h2>Aktivitas terbaru</h2>
@include('partials.audit-table', ['logs' => $recentAudit, 'showTenant' => true])
@endsection
