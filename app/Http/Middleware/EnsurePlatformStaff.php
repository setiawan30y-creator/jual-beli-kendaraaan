<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformStaff
{
    public function handle(Request $request, Closure $next, string $level = 'staff'): Response
    {
        $user = $request->user();
        $ok = match ($level) {
            'admin' => $user?->isPlatformAdmin(),
            'moderator' => $user?->canModerate(),
            default => $user?->isPlatformStaff(),
        };
        abort_unless($ok, 403, 'Halaman ini khusus staf platform.');

        return $next($request);
    }
}
