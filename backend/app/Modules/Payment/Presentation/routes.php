<?php

declare(strict_types=1);

use App\Modules\Payment\Presentation\Http\Controllers\YookassaWebhookController;
use Illuminate\Support\Facades\Route;

// Без auth:sanctum — вызывается ЮKassa, источник проверяется по IP внутри контроллера,
// см. docs/rules/04-payments-escrow.md.
Route::prefix('v1')->group(function (): void {
    Route::post('/payments/webhooks/yookassa', [YookassaWebhookController::class, 'handle']);
});
