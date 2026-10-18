@extends('layouts.dealer')
@section('heading', 'Booking & test ride')
@section('content')
<h1>Booking & test ride</h1>
@if ($bookings->isEmpty())
    <div class="card"><x-empty icon="event" title="Belum ada booking">Pembeli dapat mengajukan jadwal kunjungan dari halaman iklan.</x-empty></div>
@else
<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Jadwal</th><th>Pembeli</th><th>Iklan</th><th>Jenis</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    @foreach ($bookings as $b)
        <tr>
            <td data-label="Jadwal"><strong>{{ $b->starts_at->format('d/m/Y H:i') }}</strong></td>
            <td data-label="Pembeli">{{ $b->buyer?->name }}<br><span class="small muted">{{ $b->buyer?->phone }}</span></td>
            <td data-label="Iklan">{{ $b->listing?->title }}</td>
            <td data-label="Jenis">{{ \App\Models\Booking::TYPES[$b->booking_type] }}</td>
            <td data-label="Status"><x-status-badge :status="$b->status" :label="\App\Models\Booking::STATUSES[$b->status]" /></td>
            <td data-label="Aksi"><div class="row" style="gap:4px;justify-content:flex-end">
                @if ($b->status === 'menunggu')
                    <form method="post" action="{{ route('dealer.bookings.update', $b) }}">@csrf @method('patch')<button class="btn btn-primary btn-sm" name="status" value="dikonfirmasi">Konfirmasi</button></form>
                @endif
                @if ($b->status === 'dikonfirmasi')
                    <form method="post" action="{{ route('dealer.bookings.update', $b) }}">@csrf @method('patch')<button class="btn btn-outline btn-sm" name="status" value="selesai">Selesai</button></form>
                @endif
                @if (in_array($b->status, ['menunggu', 'dikonfirmasi']))
                    <form method="post" action="{{ route('dealer.bookings.update', $b) }}" data-confirm="Batalkan booking ini?">@csrf @method('patch')<button class="btn btn-text btn-sm" name="status" value="dibatalkan" style="color:var(--danger)">Batalkan</button></form>
                @endif
            </div></td>
        </tr>
    @endforeach
    </tbody></table></div></div>
{{ $bookings->links() }}
@endif
@endsection
