<?php

namespace App\Services;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;

/** Filter & sorting dengan whitelist (Blueprint UI §7). */
class ListingSearch
{
    public function normalize(array $in): array
    {
        $int = fn ($v) => is_numeric($v) ? (int) $v : null;

        return [
            'q' => isset($in['q']) ? mb_substr(trim((string) $in['q']), 0, 80) : null,
            'kategori' => in_array($in['kategori'] ?? null, ['motor', 'mobil'], true) ? $in['kategori'] : null,
            'kondisi' => in_array($in['kondisi'] ?? null, ['baru', 'seken'], true) ? $in['kondisi'] : null,
            'merek' => $int($in['merek'] ?? null),
            'kota' => $int($in['kota'] ?? null),
            'harga_min' => $int($in['harga_min'] ?? null),
            'harga_max' => $int($in['harga_max'] ?? null),
            'tahun_min' => $int($in['tahun_min'] ?? null),
            'tahun_max' => $int($in['tahun_max'] ?? null),
            'km_max' => $int($in['km_max'] ?? null),
            'transmisi' => in_array($in['transmisi'] ?? null, ['manual', 'otomatis', 'semi'], true) ? $in['transmisi'] : null,
            'penjual' => in_array($in['penjual'] ?? null, ['dealer', 'perorangan'], true) ? $in['penjual'] : null,
            'urut' => array_key_exists($in['urut'] ?? '', Listing::SORTS) ? $in['urut'] : 'terbaru',
        ];
    }

    public function query(array $f): Builder
    {
        $q = Listing::query()->select('listings.*')->publiclyVisible()->whereIn('listings.status', ['published', 'reserved'])
            ->join('vehicle_units as u', 'u.id', '=', 'listings.unit_id')
            ->join('vehicle_models as m', 'm.id', '=', 'u.model_id')
            ->join('vehicle_brands as b', 'b.id', '=', 'm.brand_id');

        if ($f['q']) {
            $term = '%'.str_replace(['%', '_'], '', $f['q']).'%';
            $q->where(fn ($w) => $w->where('listings.title', 'like', $term)->orWhere('m.name', 'like', $term)->orWhere('b.name', 'like', $term));
        }
        $f['kategori'] && $q->where('listings.category', $f['kategori']);
        $f['kondisi'] && $q->where('listings.condition', $f['kondisi'] === 'baru' ? 'new' : 'used');
        $f['merek'] && $q->where('b.id', $f['merek']);
        $f['kota'] && $q->where('listings.city_id', $f['kota']);
        $f['harga_min'] && $q->where('listings.price', '>=', $f['harga_min']);
        $f['harga_max'] && $q->where('listings.price', '<=', $f['harga_max']);
        $f['tahun_min'] && $q->where('u.year', '>=', $f['tahun_min']);
        $f['tahun_max'] && $q->where('u.year', '<=', $f['tahun_max']);
        $f['km_max'] && $q->where('u.odometer_km', '<=', $f['km_max']);
        $f['transmisi'] && $q->where('m.transmission', $f['transmisi']);
        if ($f['penjual'] === 'dealer') {
            $q->whereNotNull('listings.tenant_id');
        } elseif ($f['penjual'] === 'perorangan') {
            $q->whereNull('listings.tenant_id');
        }

        // Unit tersedia lebih dulu; listing unggulan diberi label transparan di UI.
        $q->orderByRaw("CASE WHEN listings.status = 'published' THEN 0 ELSE 1 END");
        match ($f['urut']) {
            'termurah' => $q->orderBy('listings.price'),
            'termahal' => $q->orderByDesc('listings.price'),
            'tahun' => $q->orderByDesc('u.year'),
            default => $q->orderByDesc('listings.published_at'),
        };

        return $q->orderByDesc('listings.id');
    }
}
