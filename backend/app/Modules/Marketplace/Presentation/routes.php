<?php

declare(strict_types=1);

use App\Modules\Marketplace\Presentation\Http\Controllers\DisputeController;
use App\Modules\Marketplace\Presentation\Http\Controllers\ListingController;
use App\Modules\Marketplace\Presentation\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/listings', [ListingController::class, 'index']);
    Route::get('/listings/me', [ListingController::class, 'mine']);
    Route::post('/listings', [ListingController::class, 'store']);
    Route::post('/listings/{listingId}/publish', [ListingController::class, 'publish']);
    Route::post('/listings/{listingId}/archive', [ListingController::class, 'archive']);
    Route::post('/listings/{listingId}/orders', [OrderController::class, 'purchase']);

    Route::get('/orders/me', [OrderController::class, 'index']);
    Route::get('/orders/{orderId}', [OrderController::class, 'show']);
    Route::post('/orders/{orderId}/confirm', [OrderController::class, 'confirm']);
    Route::post('/orders/{orderId}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{orderId}/disputes', [DisputeController::class, 'store']);

    Route::post('/disputes/{disputeId}/resolve', [DisputeController::class, 'resolve']);
});
