@extends('layouts.admin')
@section('heading', 'Tenant / dealer')
@section('content')
<h1>Tenant / dealer</h1>
<div class="chip-row mb-2"><a class="chip {{ ! $status ? 'is-active' : '' }}" href="{{ route('admin.tenants.index') }}">Semua</a>@foreach (\App\Models\Tenant::STATUSES as $s)<a class="chip {{ $status === $s ? 'is-active' : '' }}" href="{{ route('admin.tenants.index', ['status' => $s]) }}">{{ ucfirst($s) }}</a>@endforeach</div>
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Tenant</th><th>Pemilik</th><th>Paket</th><th>Status</th><th>Verifikasi</th><th class="num">Iklan aktif</th><th class="num">Anggota</th><th>Bergabung</th></tr></thead>
    <tbody>
    @foreach ($tenants as $t)
        <tr>
            <td data-label="Tenant"><a href="{{ route('admin.tenants.show', $t->id) }}"><strong>{{ $t->name }}</strong></a><br><span class="small muted">{{ $t->city?->name }}</span></td>
            <td data-label="Pemilik">{{ $t->owner->name }}</td>
            <td data-label="Paket">{{ $t->activeSubscription?->plan?->name ?? '—' }}</td>
            <td data-label="Status"><x-status-badge :status="$t->status" /></td>
            <td data-label="Verifikasi"><x-status-badge :status="$t->verification_status" /></td>
            <td data-label="Iklan aktif" class="num">{{ $t->active_listings_count }}</td>
            <td data-label="Anggota" class="num">{{ $t->memberships_count }}</td>
            <td data-label="Bergabung">{{ $t->created_at->format('d/m/Y') }}</td>
        </tr>
    @endforeach
    </tbody></table></div></div>
{{ $tenants->links() }}
@endsection
