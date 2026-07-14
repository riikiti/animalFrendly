<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Commands\RecordSwipe;

use App\Modules\Matching\Domain\Entities\PetMatch;
use App\Modules\Matching\Domain\Entities\Swipe;
use App\Modules\Matching\Domain\Enums\SwipeAction;
use App\Modules\Matching\Domain\Events\PetsMatched;
use App\Modules\Matching\Domain\Exceptions\CannotSwipeOwnPetException;
use App\Modules\Matching\Domain\Exceptions\PetAlreadySwipedException;
use App\Modules\Matching\Domain\Exceptions\PetNotFoundException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Matching\Domain\Repositories\PetMatchRepositoryInterface;
use App\Modules\Matching\Domain\Repositories\SwipeRepositoryInterface;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;
use DateTimeImmutable;

final class RecordSwipeHandler
{
    public function __construct(
        private readonly PetRepositoryInterface $pets,
        private readonly SwipeRepositoryInterface $swipes,
        private readonly PetMatchRepositoryInterface $matches,
        private readonly DomainEventDispatcherInterface $events,
    ) {}

    public function handle(RecordSwipeCommand $command): RecordSwipeResult
    {
        $swiperPetId = Id::fromString($command->swiperPetId);
        $targetPetId = Id::fromString($command->targetPetId);
        $actingUserId = Id::fromString($command->actingUserId);

        $swiperPet = $this->pets->findById($swiperPetId);

        if ($swiperPet === null) {
            throw PetNotFoundException::forId($command->swiperPetId);
        }

        if (! $swiperPet->ownerId()->equals($actingUserId)) {
            throw PetNotOwnedByActorException::create();
        }

        $targetPet = $this->pets->findById($targetPetId);

        if ($targetPet === null) {
            throw PetNotFoundException::forId($command->targetPetId);
        }

        if ($targetPet->ownerId()->equals($actingUserId)) {
            throw CannotSwipeOwnPetException::create();
        }

        if ($this->swipes->hasSwiped($swiperPetId, $targetPetId)) {
            throw PetAlreadySwipedException::create();
        }

        $action = SwipeAction::from($command->action);

        $this->swipes->record(new Swipe($swiperPetId, $targetPetId, $action, new DateTimeImmutable));

        if (! $action->isPositive()) {
            return new RecordSwipeResult(null);
        }

        if (! $this->swipes->hasLiked($targetPetId, $swiperPetId)) {
            return new RecordSwipeResult(null);
        }

        if ($this->matches->existsBetween($swiperPetId, $targetPetId)) {
            return new RecordSwipeResult(null);
        }

        $match = PetMatch::create($this->matches->nextIdentity(), $swiperPetId, $targetPetId);
        $this->matches->save($match);

        $this->events->dispatch(new PetsMatched(
            $match->id(),
            $match->petAId(),
            $match->petBId(),
            new DateTimeImmutable,
        ));

        return new RecordSwipeResult($match);
    }
}
