<?php

declare(strict_types=1);

use App\Modules\Shelter\Presentation\Http\Controllers\AdoptionRequestController;
use App\Modules\Shelter\Presentation\Http\Controllers\ShelterAnimalController;
use App\Modules\Shelter\Presentation\Http\Controllers\ShelterController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/shelters', [ShelterController::class, 'store']);
    Route::get('/shelters', [ShelterController::class, 'index']);
    Route::get('/shelters/me', [ShelterController::class, 'me']);
    Route::get('/shelters/pending-verification', [ShelterController::class, 'pendingVerification']);
    Route::post('/shelters/{shelterId}/verify', [ShelterController::class, 'verify']);
    Route::patch('/shelters/{shelterId}', [ShelterController::class, 'update']);
    Route::post('/shelters/{shelterId}/photo', [ShelterController::class, 'photo']);
    Route::get('/shelters/{shelterId}', [ShelterController::class, 'show']);
    Route::get('/shelters/{shelterId}/animals', [ShelterAnimalController::class, 'mine']);
    Route::post('/shelters/{shelterId}/animals', [ShelterAnimalController::class, 'store']);
    Route::get('/shelters/{shelterId}/adoption-requests/pending', [AdoptionRequestController::class, 'pendingForShelter']);

    Route::get('/shelter-animals', [ShelterAnimalController::class, 'index']);
    Route::post('/shelter-animals/{shelterAnimalId}/adoption-requests', [AdoptionRequestController::class, 'store']);

    Route::get('/adoption-requests/me', [AdoptionRequestController::class, 'myRequests']);
    Route::post('/adoption-requests/{adoptionRequestId}/decide', [AdoptionRequestController::class, 'decide']);
    Route::post('/adoption-requests/{adoptionRequestId}/cancel', [AdoptionRequestController::class, 'cancel']);
});
