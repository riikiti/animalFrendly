<?php

declare(strict_types=1);

use App\Modules\Profile\Presentation\Http\Controllers\PetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/pets', [PetController::class, 'index']);
    Route::post('/pets', [PetController::class, 'store']);
    Route::get('/pets/{petId}/photos', [PetController::class, 'listPhotos']);
    Route::post('/pets/{petId}/photos', [PetController::class, 'addPhoto']);
    Route::delete('/pets/{petId}/photos/{photoId}', [PetController::class, 'removePhoto']);
    Route::post('/pets/{petId}/photos/{photoId}/cover', [PetController::class, 'setPhotoCover']);
});
