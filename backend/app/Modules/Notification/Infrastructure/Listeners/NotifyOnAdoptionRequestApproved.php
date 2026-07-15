<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Modules\Shelter\Domain\Events\AdoptionRequestApproved;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;

final class NotifyOnAdoptionRequestApproved
{
    public function __construct(
        private readonly AdoptionRequestRepositoryInterface $adoptionRequests,
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function handle(AdoptionRequestApproved $event): void
    {
        $request = $this->adoptionRequests->findById($event->adoptionRequestId);

        if ($request === null) {
            return;
        }

        $this->dispatcher->notify(
            $request->requesterUserId(),
            NotificationType::AdoptionApproved,
            'Заявка на усыновление одобрена',
            ['adoption_request_id' => $event->adoptionRequestId->toString()],
        );
    }
}
