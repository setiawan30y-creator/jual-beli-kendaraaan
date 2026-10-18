@props(['theme' => null])
@php
    $t = $theme ?? config('marketplace.theme_default');
    $primary = preg_match('/^#[0-9a-fA-F]{6}$/', $t['primary'] ?? '') ? $t['primary'] : config('marketplace.theme_default.primary');
    $accent = preg_match('/^#[0-9a-fA-F]{6}$/', $t['accent'] ?? '') ? $t['accent'] : config('marketplace.theme_default.accent');
@endphp
<style>:root{--primary:{{ $primary }};--accent:{{ $accent }};--on-primary:{{ \App\Support\Color::onColor($primary) }}}</style>
<meta name="theme-color" content="{{ $primary }}">
