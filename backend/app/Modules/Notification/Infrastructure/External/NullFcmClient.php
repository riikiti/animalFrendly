<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\External;

use App\Modules\Notification\Application\Contracts\FcmClientInterface;
use Illuminate\Support\Facades\Log;

/**
 * Фоллбэк для локальной разработки, когда FCM_PROJECT_ID не заполнен — тот же принцип, что
 * NullYookassaClient. Байндится вместо FcmClient в NotificationServiceProvider::register().
 */
final class NullFcmClient implements FcmClientInterface
{
    public function send(string $token, string $title, string $body, array $data = []): void
    {
        Log::info('fcm.send', ['token' => $token, 'title' => $title, 'body' => $body, 'data' => $data]);
    }
}
