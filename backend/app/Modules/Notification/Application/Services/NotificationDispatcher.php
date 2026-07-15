<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Services;

use App\Modules\Notification\Application\Contracts\UserEmailLookupInterface;
use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Modules\Notification\Domain\Repositories\DeviceTokenRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Modules\Notification\Infrastructure\Jobs\SendPushNotificationJob;
use App\Modules\Notification\Infrastructure\Mail\NotificationMail;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Support\Facades\Mail;

/**
 * Единая точка создания уведомлений — вызывается только из слушателей доменных событий
 * других модулей, см. Infrastructure/Listeners. In-app запись создаётся синхронно и всегда;
 * push (при наличии device-токенов) и email (при наличии адреса) — best-effort в очереди,
 * сбой не влияет на остальные каналы, см. SendPushNotificationJob/NotificationMail.
 */
final class NotificationDispatcher
{
    private const string PUSH_TITLE = 'AnimalFriendly';

    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
        private readonly DeviceTokenRepositoryInterface $deviceTokens,
        private readonly UserEmailLookupInterface $emailLookup,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function notify(Id $userId, NotificationType $type, string $message, array $data = []): void
    {
        $notification = Notification::create(
            id: $this->notifications->nextIdentity(),
            userId: $userId,
            type: $type,
            message: $message,
            data: $data,
        );

        $this->notifications->save($notification);

        foreach ($this->deviceTokens->findByUser($userId) as $token) {
            SendPushNotificationJob::dispatch($token->fcmToken(), self::PUSH_TITLE, $message, $data);
        }

        $email = $this->emailLookup->emailFor($userId);

        if ($email !== null) {
            Mail::to($email)->queue(new NotificationMail($type, $message));
        }
    }
}
