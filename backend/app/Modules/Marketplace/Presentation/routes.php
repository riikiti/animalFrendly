<?php

declare(strict_types=1);

use App\Modules\Marketplace\Presentation\Http\Controllers\BreederController;
use App\Modules\Marketplace\Presentation\Http\Controllers\DisputeController;
use App\Modules\Marketplace\Presentation\Http\Controllers\ListingController;
use App\Modules\Marketplace\Presentation\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/breeders', [BreederController::class, 'store']);
    Route::get('/breeders/me', [BreederController::class, 'me']);
    Route::get('/breeders/pending-verification', [BreederController::class, 'pendingVerification']);
    Route::post('/breeders/{breederId}/verify', [BreederController::class, 'verify']);

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
