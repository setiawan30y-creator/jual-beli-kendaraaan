@props(['icon' => 'search', 'title' => 'Belum ada data'])
<div class="empty">
    <x-icon :name="$icon" />
    <h3 style="color:var(--text)">{{ $title }}</h3>
    <div>{{ $slot }}</div>
</div>
