@props(['status', 'label' => null])
@php
$map = [
  'published' => 'success', 'available' => 'success', 'active' => 'success', 'terverifikasi' => 'success', 'verified' => 'success', 'menang' => 'success', 'dikonfirmasi' => 'success', 'selesai' => 'success', 'paid' => 'success', 'posted' => 'success',
  'pending_review' => 'warning', 'menunggu' => 'warning', 'menunggu_verifikasi' => 'warning', 'reserved' => 'warning', 'pending' => 'warning', 'inspection' => 'warning', 'maintenance' => 'warning', 'negosiasi' => 'warning', 'estimate' => 'warning', 'open' => 'warning', 'trialing' => 'warning', 'past_due' => 'warning',
  'rejected' => 'danger', 'suspended' => 'danger', 'perlu_perbaikan' => 'danger', 'dibatalkan' => 'danger', 'closed' => 'danger', 'failed' => 'danger', 'tidak_lanjut' => 'danger', 'reversed' => 'danger', 'disabled' => 'danger',
  'baru' => 'info', 'dihubungi' => 'info', 'booking' => 'info', 'draft' => '', 'sold' => 'info', 'unverified' => '',
];
$tone = $map[$status] ?? '';
@endphp
<span class="badge {{ $tone ? 'badge-'.$tone : '' }}">{{ $label ?? str_replace('_', ' ', ucfirst($status)) }}</span>
