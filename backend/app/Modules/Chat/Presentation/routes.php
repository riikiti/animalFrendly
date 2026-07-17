<?php

declare(strict_types=1);

use App\Modules\Chat\Presentation\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/conversations', [ChatController::class, 'index']);
    Route::get('/matches/{matchId}/conversation', [ChatController::class, 'conversationForMatch']);
    Route::get('/adoption-requests/{adoptionRequestId}/conversation', [ChatController::class, 'conversationForAdoptionRequest']);
    Route::post('/shelters/{shelterId}/conversations', [ChatController::class, 'conversationForShelter']);
    Route::post('/users/{userId}/conversations', [ChatController::class, 'conversationForUser']);
    Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'messages']);
    Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage']);
});
