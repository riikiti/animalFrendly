<?php

declare(strict_types=1);

use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Enums\NotificationChannel;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Shared\Domain\ValueObjects\Id;

it('creates an unread in-app notification', function (): void {
    $notification = Notification::create(
        id: Id::generate(),
        userId: Id::generate(),
        type: NotificationType::NewMatch,
        message: 'У вас новый мэтч!',
        data: ['match_id' => 'abc'],
    );

    expect($notification->readAt())->toBeNull()
        ->and($notification->channel())->toBe(NotificationChannel::InApp)
        ->and($notification->data())->toBe(['match_id' => 'abc']);
});

it('marks a notification as read idempotently', function (): void {
    $notification = Notification::create(Id::generate(), Id::generate(), NotificationType::NewMessage, 'Новое сообщение');

    $notification->markRead();
    $firstReadAt = $notification->readAt();

    $notification->markRead();

    expect($notification->readAt())->toBe($firstReadAt);
});
