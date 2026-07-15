<?php

declare(strict_types=1);

use App\Modules\Matching\Domain\Events\PetsMatched;
use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Modules\Notification\Infrastructure\Listeners\NotifyOnPetsMatched;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

it('notifies both pet owners about a new match', function (): void {
    $ownerA = Id::generate();
    $ownerB = Id::generate();
    $petA = Pet::create(Id::generate(), $ownerA, 1, null, 'A', PetSex::Male, null, PetPurpose::Social, null, false);
    $petB = Pet::create(Id::generate(), $ownerB, 1, null, 'B', PetSex::Female, null, PetPurpose::Social, null, false);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->with($petA->id())->andReturn($petA);
    $pets->shouldReceive('findById')->with($petB->id())->andReturn($petB);

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->twice()->andReturn(Id::generate(), Id::generate());
    $notifications->shouldReceive('save')->twice()->with(Mockery::on(
        fn (Notification $n) => $n->userId()->equals($ownerA) || $n->userId()->equals($ownerB),
    ));

    $listener = new NotifyOnPetsMatched($pets, new NotificationDispatcher($notifications));
    $listener->handle(new PetsMatched(Id::generate(), $petA->id(), $petB->id(), new DateTimeImmutable));
});
