<?php

declare(strict_types=1);

use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

it('creates and saves a notification for the target user', function (): void {
    $userId = Id::generate();

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $notifications->shouldReceive('save')->once()->with(Mockery::on(
        fn (Notification $n) => $n->userId()->equals($userId)
            && $n->type() === NotificationType::DealCompleted
            && $n->message() === 'Сделка завершена, выплата отправлена'
            && $n->data() === ['order_id' => 'abc'],
    ));

    $dispatcher = new NotificationDispatcher($notifications);
    $dispatcher->notify($userId, NotificationType::DealCompleted, 'Сделка завершена, выплата отправлена', ['order_id' => 'abc']);
});
