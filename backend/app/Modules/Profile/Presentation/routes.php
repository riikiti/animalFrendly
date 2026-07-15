<?php

declare(strict_types=1);

use App\Modules\Profile\Presentation\Http\Controllers\PetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/pets', [PetController::class, 'index']);
    Route::post('/pets', [PetController::class, 'store']);
    Route::post('/pets/{petId}/photo', [PetController::class, 'setPhoto']);
    Route::delete('/pets/{petId}/photo', [PetController::class, 'removePhoto']);
});
