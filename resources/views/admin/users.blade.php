@extends('layouts.admin')
@section('heading', 'Pengguna')
@section('content')
<h1>Pengguna</h1>
<form class="row mb-2" method="get"><input class="input" style="flex:1" name="q" value="{{ $q }}" placeholder="Cari nama atau email" aria-label="Cari pengguna"><button class="btn btn-outline">Cari</button></form>
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Nama</th><th>Peran platform</th><th class="num">Tenant</th><th>Status</th><th>Login terakhir</th><th></th></tr></thead>
    <tbody>
    @foreach ($users as $u)
        <tr>
            <td data-label="Nama"><strong>{{ $u->name }}</strong><br><span class="small muted">{{ $u->email }}</span></td>
            <td data-label="Peran">{{ str_replace('_', ' ', $u->platform_role) }}</td>
            <td data-label="Tenant" class="num">{{ $u->memberships_count }}</td>
            <td data-label="Status"><x-status-badge :status="$u->status" /></td>
            <td data-label="Login">{{ $u->last_login_at?->diffForHumans() ?? '—' }}</td>
            <td>@if ($u->id !== auth()->id() && $u->platform_role !== 'super_admin')
                <details><summary class="small" style="cursor:pointer">{{ $u->status === 'active' ? 'Tangguhkan' : 'Aktifkan' }}</summary>
                <form method="post" action="{{ route('admin.users.status', $u) }}" class="stack mt-1">@csrf
                    <input type="hidden" name="status" value="{{ $u->status === 'active' ? 'suspended' : 'active' }}">
                    <input class="input" name="reason" required placeholder="Alasan" aria-label="Alasan"><button class="btn btn-outline btn-sm">Proses</button></form></details>
            @endif</td>
        </tr>
    @endforeach
    </tbody></table></div></div>
{{ $users->links() }}
@endsection
