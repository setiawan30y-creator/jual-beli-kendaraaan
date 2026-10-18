<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menandai halaman publik untuk tamu agar boleh disimpan service worker sebagai fallback offline.
 * Halaman akun/dealer/admin dan respons untuk pengguna login tidak pernah ditandai.
 */
class MarkPublicPageCacheable
{
    protected array $routes = ['home', 'catalog', 'catalog.*', 'listings.show', 'dealers.*', 'pages.*', 'offline'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->isMethod('GET') && ! $request->user() && $request->routeIs(...$this->routes) && $response->getStatusCode() === 200) {
            $response->headers->set('X-PWA-Cacheable', '1');
        }

        return $response;
    }
}
