<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiDocumentationController;
use App\Http\Controllers\Api\LinkApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::get('/openapi.json', ApiDocumentationController::class)
        ->withoutMiddleware('api-token:read')
        ->name('api.v1.openapi');

    Route::middleware('api-token:read')->group(function (): void {
        Route::get('/me', [LinkApiController::class, 'me'])->name('api.v1.me');
        Route::get('/links', [LinkApiController::class, 'index'])->name('api.v1.links.index');
        Route::get('/links/{link}', [LinkApiController::class, 'show'])->name('api.v1.links.show');
    });

    Route::get('/links/{link}/analytics', [LinkApiController::class, 'analytics'])
        ->middleware('api-token:analytics')
        ->name('api.v1.links.analytics');

    Route::post('/events', [LinkApiController::class, 'trackEvent'])
        ->middleware(['api-token:events', 'api-idempotency'])
        ->name('api.v1.events.store');

    Route::middleware(['api-token:write', 'api-idempotency'])->group(function (): void {
        Route::post('/links', [LinkApiController::class, 'store'])->name('api.v1.links.store');
        Route::patch('/links/{link}', [LinkApiController::class, 'update'])->name('api.v1.links.update');
        Route::delete('/links/{link}', [LinkApiController::class, 'destroy'])->name('api.v1.links.destroy');
    });
});
