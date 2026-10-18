{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach (['home', 'catalog', 'catalog.motor', 'catalog.motor.new', 'catalog.motor.used', 'catalog.car', 'dealers.index', 'pages.guide', 'pages.credit'] as $r)
<url><loc>{{ route($r) }}</loc></url>
@endforeach
@foreach ($dealers as $d)
<url><loc>{{ route('dealers.show', $d) }}</loc><lastmod>{{ $d->updated_at->toAtomString() }}</lastmod></url>
@endforeach
@foreach ($listings as $l)
<url><loc>{{ route('listings.show', $l) }}</loc><lastmod>{{ $l->updated_at->toAtomString() }}</lastmod></url>
@endforeach
</urlset>
