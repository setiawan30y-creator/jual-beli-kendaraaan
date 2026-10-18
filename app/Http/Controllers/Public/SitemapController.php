<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Tenant;

class SitemapController extends Controller
{
    public function index()
    {
        $listings = Listing::publiclyVisible()->where('listings.status', 'published')->latest('updated_at')->take(5000)->get(['slug', 'updated_at']);
        $dealers = Tenant::where('status', 'active')->get(['slug', 'updated_at']);

        return response()->view('public.sitemap', compact('listings', 'dealers'))->header('Content-Type', 'application/xml');
    }
}
