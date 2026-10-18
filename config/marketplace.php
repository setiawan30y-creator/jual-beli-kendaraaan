<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Marketplace Kendaraan
|--------------------------------------------------------------------------
| Nama merek final belum ditentukan (Blueprint §3) — ubah melalui .env
| tanpa mengubah kode.
*/

return [

    'brand_name' => env('MARKETPLACE_BRAND', 'MotoMarket'),
    'tagline' => env('MARKETPLACE_TAGLINE', 'Jual beli motor & mobil baru dan seken'),
    'support_email' => env('MARKETPLACE_SUPPORT_EMAIL', 'bantuan@example.com'),
    'developer_signature' => '@2026-Alifiandra',

    // Masa tayang default iklan (hari)
    'listing_ttl_days' => (int) env('LISTING_TTL_DAYS', 60),

    // Batas iklan aktif penjual perorangan (anti-spam)
    'private_seller_listing_limit' => (int) env('PRIVATE_SELLER_LISTING_LIMIT', 3),

    'uploads' => [
        'max_kb' => 5120, // 5 MB per gambar
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'per_category' => 3,
    ],

    // Default tema platform (Blueprint §17.4.1). Tenant dapat menimpa sesuai izin.
    'theme_default' => [
        'primary' => '#1a5fd0',
        'accent' => '#f59e0b',
    ],

    'theme_presets' => [
        'biru' => ['label' => 'Biru (default)', 'primary' => '#1a5fd0', 'accent' => '#f59e0b'],
        'hijau' => ['label' => 'Hijau tua', 'primary' => '#13795b', 'accent' => '#e0a100'],
        'merah' => ['label' => 'Merah marun', 'primary' => '#b42335', 'accent' => '#2563eb'],
        'ungu' => ['label' => 'Ungu', 'primary' => '#6236c4', 'accent' => '#0e9f8e'],
        'grafit' => ['label' => 'Grafit', 'primary' => '#2f3a4a', 'accent' => '#e2541b'],
    ],

    /*
    | Permission per role tenant (RBAC + tenant scoping, Blueprint §3 & §17.5).
    | '*' = semua izin tenant.
    */
    'tenant_permissions' => [
        'tenant_owner' => ['*'],
        'tenant_admin' => ['dashboard.view', 'units.view', 'units.manage', 'units.costs', 'media.private', 'media.verify', 'listings.manage', 'leads.view_all', 'leads.manage', 'bookings.manage', 'members.manage', 'settings.manage', 'reports.view'],
        'branch_manager' => ['dashboard.view', 'units.view', 'units.manage', 'units.costs', 'media.private', 'listings.manage', 'leads.view_all', 'leads.manage', 'bookings.manage', 'reports.view'],
        'sales' => ['dashboard.view', 'units.view', 'leads.manage', 'bookings.manage'],
        'inventory_staff' => ['dashboard.view', 'units.view', 'units.manage', 'media.private', 'listings.manage'],
        'finance_staff' => ['dashboard.view', 'units.view', 'units.costs', 'reports.view', 'billing.view'],
        'inspector' => ['dashboard.view', 'units.view', 'media.private', 'media.verify'],
        'read_only' => ['dashboard.view', 'units.view'],
    ],

    // Widget cuaca (Blueprint: Widget Cuaca dan Lokasi). Open-Meteo tidak memerlukan API key.
    'weather' => [
        'enabled' => (bool) env('WEATHER_ENABLED', true),
        'provider_url' => env('WEATHER_PROVIDER_URL', 'https://api.open-meteo.com/v1/forecast'),
        'cache_minutes' => 30,
        'unit' => env('WEATHER_UNIT', 'celsius'),
    ],

];
