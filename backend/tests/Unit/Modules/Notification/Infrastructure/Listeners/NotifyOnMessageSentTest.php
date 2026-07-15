<?php

declare(strict_types=1);

use App\Modules\Chat\Domain\Events\MessageSent;
use App\Modules\Notification\Application\Contracts\UserEmailLookupInterface;
use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Repositories\DeviceTokenRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Modules\Notification\Infrastructure\Listeners\NotifyOnMessageSent;
use App\Shared\Domain\ValueObjects\Id;

it('notifies the message recipient', function (): void {
    $recipientId = Id::generate();

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $notifications->shouldReceive('save')->once()->with(Mockery::on(
        fn (Notification $n) => $n->userId()->equals($recipientId),
    ));

    $deviceTokens = Mockery::mock(DeviceTokenRepositoryInterface::class);
    $deviceTokens->shouldReceive('findByUser')->once()->andReturn([]);

    $emailLookup = Mockery::mock(UserEmailLookupInterface::class);
    $emailLookup->shouldReceive('emailFor')->once()->andReturn(null);

    $listener = new NotifyOnMessageSent(new NotificationDispatcher($notifications, $deviceTokens, $emailLookup));
    $listener->handle(new MessageSent(Id::generate(), Id::generate(), Id::generate(), $recipientId, new DateTimeImmutable));
});
