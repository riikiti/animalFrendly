<?php

declare(strict_types=1);

use App\Modules\Moderation\Presentation\Http\Controllers\BanController;
use App\Modules\Moderation\Presentation\Http\Controllers\ReportController;
use App\Modules\Moderation\Presentation\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/moderation/reports', [ReportController::class, 'index']);
    Route::post('/moderation/reports/{reportId}/review', [ReportController::class, 'review']);
    Route::post('/moderation/reports/{reportId}/dismiss', [ReportController::class, 'dismiss']);

    Route::post('/moderation/users/{userId}/ban', [BanController::class, 'ban']);
    Route::post('/moderation/users/{userId}/unban', [BanController::class, 'unban']);

    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/users/{userId}/reviews', [ReviewController::class, 'index']);
    Route::get('/users/{userId}/rating', [ReviewController::class, 'rating']);
});
