<?php

declare(strict_types=1);

use App\Modules\Shop\Presentation\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/shop')->group(function (): void {
    Route::get('/categories', [ShopController::class, 'categories']);
    Route::get('/products', [ShopController::class, 'products']);
    Route::get('/products/{id}', [ShopController::class, 'product']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/my-products', [ShopController::class, 'myProducts']);
        Route::post('/products', [ShopController::class, 'createProduct']);
        Route::patch('/products/{id}', [ShopController::class, 'updateProduct']);
        Route::post('/products/{id}/archive', [ShopController::class, 'archiveProduct']);

        Route::get('/cart', [ShopController::class, 'cart']);
        Route::post('/cart', [ShopController::class, 'addToCart']);
        Route::patch('/cart/{productId}', [ShopController::class, 'updateCartItem']);
        Route::delete('/cart/{productId}', [ShopController::class, 'removeCartItem']);
    });
});
