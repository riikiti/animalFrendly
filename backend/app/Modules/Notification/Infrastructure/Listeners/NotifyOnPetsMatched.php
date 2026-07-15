<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Listeners;

use App\Modules\Matching\Domain\Events\PetsMatched;
use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;

final class NotifyOnPetsMatched
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function handle(PetsMatched $event): void
    {
        $petA = $this->pets->findById($event->petAId);
        $petB = $this->pets->findById($event->petBId);

        foreach ([$petA, $petB] as $pet) {
            if ($pet !== null) {
                $this->dispatcher->notify($pet->ownerId(), NotificationType::NewMatch, 'У вас новый мэтч!', [
                    'match_id' => $event->matchId->toString(),
                ]);
            }
        }
    }
}
