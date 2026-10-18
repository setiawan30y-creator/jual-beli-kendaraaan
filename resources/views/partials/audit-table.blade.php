<div class="card"><div class="table-wrap"><table class="table stacked">
    <thead><tr><th>Waktu</th><th>Aktor</th>@if($showTenant)<th>Tenant</th>@endif<th>Aksi</th><th>Objek</th><th>Perubahan</th></tr></thead>
    <tbody>
    @forelse ($logs as $a)
        <tr>
            <td data-label="Waktu" class="nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
            <td data-label="Aktor">{{ $a->actor?->name ?? 'Sistem/tamu' }}</td>
            @if($showTenant)<td data-label="Tenant">{{ $a->tenant?->name ?? '—' }}</td>@endif
            <td data-label="Aksi"><code>{{ $a->action }}</code></td>
            <td data-label="Objek">{{ $a->resource_type }} #{{ $a->resource_id }}</td>
            <td data-label="Perubahan" class="small">
                @if ($a->before_json)<span class="muted">{{ json_encode($a->before_json, JSON_UNESCAPED_UNICODE) }}</span> → @endif
                {{ $a->after_json ? json_encode($a->after_json, JSON_UNESCAPED_UNICODE) : '' }}
                @if ($a->reason)<br><em>{{ $a->reason }}</em>@endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">Belum ada log.</td></tr>
    @endforelse
    </tbody></table></div></div>
@if (method_exists($logs, 'links')){{ $logs->links() }}@endif
