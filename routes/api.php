<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API /api/v1 — kontrak resmi untuk web & Flutter (Blueprint §7)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // Publik
    Route::get('/brands', [V1\CatalogController::class, 'brands']);
    Route::get('/vehicle-models', [V1\CatalogController::class, 'models']);
    Route::get('/locations', [V1\CatalogController::class, 'locations']);
    Route::get('/listings', [V1\ListingController::class, 'index']);
    Route::get('/listings/{listing}', [V1\ListingController::class, 'show']);
    Route::get('/dealers/{tenant}', [V1\ListingController::class, 'dealer']);
    Route::post('/listings/{listing}/leads', [V1\ListingController::class, 'lead'])->middleware('throttle:leads');

    // Autentikasi token (Sanctum)
    Route::post('/auth/register', [V1\AuthController::class, 'register'])->middleware('throttle:login');
    Route::post('/auth/login', [V1\AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/auth/logout', [V1\AuthController::class, 'logout']);
        Route::get('/me', [V1\MeController::class, 'show']);
        Route::patch('/me', [V1\MeController::class, 'update']);
        Route::get('/me/favorites', [V1\MeController::class, 'favorites']);
        Route::post('/me/favorites', [V1\MeController::class, 'addFavorite']);
        Route::delete('/me/favorites/{listingId}', [V1\MeController::class, 'removeFavorite']);
        Route::get('/me/leads', [V1\MeController::class, 'leads']);
        Route::post('/bookings', [V1\MeController::class, 'book'])->middleware('throttle:leads');

        // Dealer (tenant-scoped; tenant divalidasi dari keanggotaan, bukan dari input client)
        Route::middleware('tenant')->prefix('dealer')->group(function () {
            Route::get('/inventory', [V1\DealerController::class, 'inventory']);
            Route::post('/inventory', [V1\DealerController::class, 'storeUnit']);
            Route::patch('/inventory/{unit}', [V1\DealerController::class, 'updateUnit']);
            Route::get('/leads', [V1\DealerController::class, 'leads']);
            Route::patch('/leads/{lead}', [V1\DealerController::class, 'updateLead']);
            Route::get('/subscription', [V1\DealerController::class, 'subscription']);
        });
    });
});
