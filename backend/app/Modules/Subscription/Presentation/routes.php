<?php

declare(strict_types=1);

use App\Modules\Subscription\Presentation\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans']);
    Route::get('/subscriptions/me', [SubscriptionController::class, 'me']);
    Route::post('/subscriptions', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel']);
});
