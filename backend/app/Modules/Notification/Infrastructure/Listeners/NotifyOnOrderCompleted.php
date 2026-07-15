<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Enums\NotificationType;

final class NotifyOnOrderCompleted
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function handle(OrderCompleted $event): void
    {
        $this->dispatcher->notify(
            $event->sellerId,
            NotificationType::DealCompleted,
            'Сделка завершена, выплата отправлена',
            ['order_id' => $event->orderId->toString()],
        );
    }
}
