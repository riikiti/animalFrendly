<?php

declare(strict_types=1);

use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Modules\Notification\Infrastructure\Listeners\NotifyOnOrderCompleted;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

it('notifies the seller when the order is completed', function (): void {
    $sellerId = Id::generate();

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $notifications->shouldReceive('save')->once()->with(Mockery::on(
        fn (Notification $n) => $n->userId()->equals($sellerId),
    ));

    $listener = new NotifyOnOrderCompleted(new NotificationDispatcher($notifications));
    $listener->handle(new OrderCompleted(Id::generate(), $sellerId, Money::fromMinorUnits(100_000), new DateTimeImmutable));
});
