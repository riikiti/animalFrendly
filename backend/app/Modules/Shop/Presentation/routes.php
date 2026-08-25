<?php

declare(strict_types=1);

use App\Modules\Shop\Presentation\Http\Controllers\ShopController;
use App\Modules\Shop\Presentation\Http\Controllers\ShopOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/shop')->group(function (): void {
    Route::get('/categories', [ShopController::class, 'categories']);
    Route::get('/products', [ShopController::class, 'products']);
    Route::get('/products/{id}', [ShopController::class, 'product']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/my-products', [ShopController::class, 'myProducts']);
        Route::post('/products', [ShopController::class, 'createProduct']);
        Route::patch('/products/{id}', [ShopController::class, 'updateProduct']);
        Route::post('/products/{id}/photo', [ShopController::class, 'uploadProductPhoto']);
        Route::post('/products/{id}/archive', [ShopController::class, 'archiveProduct']);

        Route::get('/delivery-options', [ShopOrderController::class, 'deliveryOptions']);
        Route::post('/orders', [ShopOrderController::class, 'store']);
        Route::get('/orders', [ShopOrderController::class, 'index']);
        Route::get('/orders/{id}', [ShopOrderController::class, 'show']);
        Route::post('/orders/{id}/ship', [ShopOrderController::class, 'ship']);
        Route::post('/orders/{id}/confirm', [ShopOrderController::class, 'confirm']);
        Route::post('/orders/{id}/dispute', [ShopOrderController::class, 'dispute']);
        Route::post('/orders/{id}/cancel', [ShopOrderController::class, 'cancel']);

        Route::get('/cart', [ShopController::class, 'cart']);
        Route::post('/cart', [ShopController::class, 'addToCart']);
        Route::patch('/cart/{productId}', [ShopController::class, 'updateCartItem']);
        Route::delete('/cart/{productId}', [ShopController::class, 'removeCartItem']);
    });
});
