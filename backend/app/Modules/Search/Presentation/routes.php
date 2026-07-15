<?php

declare(strict_types=1);

use App\Modules\Search\Presentation\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/search')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/pets', [SearchController::class, 'pets']);
    Route::get('/listings', [SearchController::class, 'listings']);
});
