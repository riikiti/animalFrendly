<?php

declare(strict_types=1);

use App\Modules\Notification\Presentation\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/device-tokens', [NotificationController::class, 'registerDeviceToken']);
    Route::delete('/notifications/device-tokens/{fcmToken}', [NotificationController::class, 'unregisterDeviceToken']);
});
