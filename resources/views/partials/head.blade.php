<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($pageTitle) ? $pageTitle.' · '.$brandName : $brandName.' — '.$brandTagline }}</title>
<meta name="description" content="{{ $metaDescription ?? 'Cari, bandingkan, dan hubungi penjual motor & mobil baru maupun seken dari dealer terverifikasi dan penjual perorangan.' }}">
@isset($canonical)<link rel="canonical" href="{{ $canonical }}">@endisset
@isset($noindex)<meta name="robots" content="noindex">@endisset
<meta property="og:site_name" content="{{ $brandName }}">
<meta property="og:title" content="{{ $pageTitle ?? $brandName }}">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
@isset($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endisset
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" href="{{ asset('icons/icon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
