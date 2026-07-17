<?php

declare(strict_types=1);

use App\Modules\Matching\Presentation\Http\Controllers\BoostController;
use App\Modules\Matching\Presentation\Http\Controllers\SwipeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/pets/{petId}/candidates', [SwipeController::class, 'candidates']);
    Route::post('/pets/{petId}/swipes', [SwipeController::class, 'store']);
    Route::get('/pets/{petId}/matches', [SwipeController::class, 'matches']);
    Route::get('/pets/{petId}/pending-likes', [SwipeController::class, 'pendingLikes']);
    Route::post('/pets/{petId}/boost', [BoostController::class, 'store']);
});
