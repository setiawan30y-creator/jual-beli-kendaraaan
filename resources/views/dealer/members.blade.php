@extends('layouts.dealer')
@section('heading', 'Anggota & peran')
@section('content')
<div class="row-between mb-2"><h1 class="mb-0">Anggota & peran</h1><span class="small muted">Kuota staf: <strong>{{ $usage['used'] }} / {{ $usage['limit'] ?? '∞' }}</strong></span></div>
<div class="detail">
    <div class="card"><div class="table-wrap"><table class="table stacked">
        <thead><tr><th>Nama</th><th>Peran</th><th>Cabang</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach ($members as $m)
            <tr>
                <td data-label="Nama"><strong>{{ $m->user->name }}</strong><br><span class="small muted">{{ $m->user->email }}</span></td>
                @if ($m->role === 'tenant_owner')
                    <td data-label="Peran">{{ $m->roleLabel() }}</td><td data-label="Cabang">{{ $m->branch?->name ?? 'Semua' }}</td><td data-label="Status"><x-status-badge :status="$m->status" label="Aktif" /></td><td></td>
                @else
                    <td colspan="4" data-label="Ubah">
                        <form method="post" action="{{ route('dealer.members.update', $m) }}" class="row" style="gap:6px">@csrf @method('patch')
                            <select class="select" name="role" style="width:auto" aria-label="Peran">@foreach ($roles as $k => $v)<option value="{{ $k }}" @selected($m->role === $k)>{{ $v }}</option>@endforeach</select>
                            <select class="select" name="branch_id" style="width:auto" aria-label="Cabang"><option value="">Semua cabang</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected($m->branch_id === $b->id)>{{ $b->name }}</option>@endforeach</select>
                            <select class="select" name="status" style="width:auto" aria-label="Status"><option value="active" @selected($m->status === 'active')>Aktif</option><option value="disabled" @selected($m->status === 'disabled')>Nonaktif</option></select>
                            <button class="btn btn-outline btn-sm">Simpan</button>
                        </form>
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody></table></div></div>
    <aside class="detail-aside">
        <form method="post" action="{{ route('dealer.members.store') }}" class="card card-body stack">
            @csrf
            <h2 class="card-title mb-0">Tambah anggota</h2>
            <p class="small muted mb-0">Staf mendaftar akun terlebih dahulu, lalu tambahkan dengan emailnya.</p>
            <x-field name="email" label="Email staf" type="email" required />
            <x-field name="role" label="Peran" type="select">@foreach ($roles as $k => $v)<option value="{{ $k }}" @selected($k === 'sales')>{{ $v }}</option>@endforeach</x-field>
            <x-field name="branch_id" label="Cabang" type="select"><option value="">Semua cabang</option>@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</x-field>
            <button class="btn btn-primary">Tambahkan</button>
            <details class="small"><summary>Ringkasan hak akses peran</summary>
                <ul class="muted" style="padding-left:1rem">
                    <li><strong>Admin dealer</strong>: operasional penuh kecuali kepemilikan.</li>
                    <li><strong>Manajer cabang</strong>: unit, iklan, leads, laporan.</li>
                    <li><strong>Sales</strong>: hanya leads yang ditugaskan & booking.</li>
                    <li><strong>Staf inventaris</strong>: unit & foto, tanpa biaya internal.</li>
                    <li><strong>Staf keuangan</strong>: biaya/HPP & langganan.</li>
                    <li><strong>Inspektor</strong>: lihat & verifikasi bukti.</li>
                </ul></details>
        </form>
    </aside>
</div>
@endsection
