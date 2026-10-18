<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsurePlatformStaff;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'platform' => EnsurePlatformStaff::class,
            'active' => EnsureActiveUser::class,
        ]);
        $middleware->api(prepend: [AssignRequestId::class]);
        $middleware->web(append: [EnsureActiveUser::class, \App\Http\Middleware\MarkPublicPageCacheable::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('account.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Envelope error konsisten untuk API v1: data, meta, errors, request_id.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $requestId = $request->attributes->get('request_id');
            [$status, $errors] = match (true) {
                $e instanceof ValidationException => [422, collect($e->errors())->map(fn ($m, $f) => ['field' => $f, 'message' => $m[0]])->values()->all()],
                $e instanceof AuthenticationException => [401, [['message' => 'Tidak terautentikasi.']]],
                $e instanceof ModelNotFoundException => [404, [['message' => 'Data tidak ditemukan.']]],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), [['message' => $e->getMessage() ?: 'Permintaan tidak dapat diproses.']]],
                default => [500, [['message' => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada server.']]],
            };

            return response()->json(['data' => null, 'meta' => (object) [], 'errors' => $errors, 'request_id' => $requestId], $status);
        });
    })->create();
