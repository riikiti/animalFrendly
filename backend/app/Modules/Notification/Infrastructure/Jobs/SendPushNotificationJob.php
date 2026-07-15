<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Jobs;

use App\Modules\Notification\Application\Contracts\FcmClientInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Best-effort: сбой отправки push не должен ронять доставку остальных каналов
 * (in-app уже создан синхронно в NotificationDispatcher::notify()), поэтому исключение
 * ловится и логируется, а не пробрасывается дальше.
 */
final class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $token,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    public function handle(FcmClientInterface $fcm): void
    {
        try {
            $fcm->send($this->token, $this->title, $this->body, $this->data);
        } catch (Throwable $e) {
            Log::warning('fcm.send.failed', ['token' => $this->token, 'error' => $e->getMessage()]);
        }
    }
}
