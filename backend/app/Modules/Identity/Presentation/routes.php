<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Http\Controllers\AuthController;
use App\Modules\Identity\Presentation\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Коды из СМС дороже обычных запросов и годятся для перебора, поэтому лимит жёстче.
    Route::middleware('throttle:6,1')->group(function (): void {
        Route::post('/phone-code', [AuthController::class, 'requestPhoneCode']);
        Route::post('/phone-code/login', [AuthController::class, 'loginWithPhoneCode']);
        Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    });

    Route::get('/social/providers', [SocialAuthController::class, 'providers']);
    Route::get('/social/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('/social/{provider}/callback', [SocialAuthController::class, 'callback']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me', [AuthController::class, 'updateProfile']);
        Route::post('/me/avatar', [AuthController::class, 'uploadAvatar']);
    });
});
