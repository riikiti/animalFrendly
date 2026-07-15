<?php

declare(strict_types=1);

use App\Modules\Admin\Presentation\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/summary', [AdminController::class, 'summary']);
    Route::get('/audit-log', [AdminController::class, 'auditLog']);
});
