<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/** Envelope JSON konsisten: data, meta, errors, request_id (Blueprint §7). */
class ApiResponse
{
    public static function ok(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => (object) $meta,
            'errors' => [],
            'request_id' => request()->attributes->get('request_id'),
        ], $status);
    }

    public static function paginated(LengthAwarePaginator $page, callable $map): JsonResponse
    {
        return self::ok(collect($page->items())->map($map)->values(), [
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ]);
    }

    public static function error(string $message, int $status, array $errors = []): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => (object) [],
            'errors' => $errors ?: [['message' => $message]],
            'request_id' => request()->attributes->get('request_id'),
        ], $status);
    }
}
