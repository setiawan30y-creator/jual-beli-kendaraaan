<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('components.pagination');

        foreach ([
            'unit' => \App\Models\VehicleUnit::class, 'media' => \App\Models\UnitMedia::class,
            'cost' => \App\Models\VehicleCostEntry::class, 'lead' => \App\Models\Lead::class,
            'booking' => \App\Models\Booking::class, 'membership' => \App\Models\TenantMembership::class,
            'report' => \App\Models\ListingReport::class, 'plan' => \App\Models\Plan::class, 'user' => \App\Models\User::class,
            'party' => \App\Models\Party::class, 'document' => \App\Models\PartyDocument::class, 'part' => \App\Models\SparePart::class,
        ] as $param => $class) {
            Route::model($param, $class);
        }

        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('leads', fn (Request $r) => Limit::perMinute(6)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('reports', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));

        View::composer('*', function ($view) {
            static $shared = null;
            if ($shared === null) {
                $shared = [
                    'brandName' => config('marketplace.brand_name'),
                    'brandTagline' => config('marketplace.tagline'),
                ];
            }
            $view->with($shared);
        });
    }
}
