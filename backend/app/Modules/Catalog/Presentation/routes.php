<?php

declare(strict_types=1);

use App\Modules\Catalog\Presentation\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/catalog')->group(function (): void {
    Route::get('/species', [CatalogController::class, 'species']);
    Route::get('/species/{speciesSlug}/breeds', [CatalogController::class, 'breeds']);
});
