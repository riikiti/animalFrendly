<?php

declare(strict_types=1);

use App\Modules\Matching\Application\Commands\RecordSwipe\RecordSwipeCommand;
use App\Modules\Matching\Application\Commands\RecordSwipe\RecordSwipeHandler;
use App\Modules\Matching\Domain\Exceptions\CannotSwipeOwnPetException;
use App\Modules\Matching\Domain\Exceptions\PetAlreadySwipedException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Matching\Domain\Repositories\PetMatchRepositoryInterface;
use App\Modules\Matching\Domain\Repositories\SwipeRepositoryInterface;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;

function makeTestPet(Id $id, Id $ownerId): Pet
{
    return Pet::create(
        id: $id,
        ownerId: $ownerId,
        speciesId: 1,
        breedId: null,
        name: 'Питомец',
        sex: PetSex::Male,
        birthdate: null,
        purpose: PetPurpose::Social,
        description: null,
        isVaccinated: false,
    );
}

it('records a like without creating a match when the target has not liked back', function (): void {
    $actingUserId = Id::generate();
    $swiperPetId = Id::generate();
    $targetPetId = Id::generate();

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->with(Mockery::on(fn ($id) => $id->equals($swiperPetId)))
        ->andReturn(makeTestPet($swiperPetId, $actingUserId));
    $pets->shouldReceive('findById')->with(Mockery::on(fn ($id) => $id->equals($targetPetId)))
        ->andReturn(makeTestPet($targetPetId, Id::generate()));

    $swipes = Mockery::mock(SwipeRepositoryInterface::class);
    $swipes->shouldReceive('hasSwiped')->once()->andReturn(false);
    $swipes->shouldReceive('record')->once();
    $swipes->shouldReceive('hasLiked')->once()->andReturn(false);

    $matches = Mockery::mock(PetMatchRepositoryInterface::class);
    $matches->shouldNotReceive('save');

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldNotReceive('dispatch');

    $handler = new RecordSwipeHandler($pets, $swipes, $matches, $events);
    $result = $handler->handle(new RecordSwipeCommand($actingUserId->toString(), $swiperPetId->toString(), $targetPetId->toString(), 'like'));

    expect($result->isMatch())->toBeFalse();
});

it('creates a match and dispatches PetsMatched when the target had already liked the swiper', function (): void {
    $actingUserId = Id::generate();
    $swiperPetId = Id::generate();
    $targetPetId = Id::generate();

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->with(Mockery::on(fn ($id) => $id->equals($swiperPetId)))
        ->andReturn(makeTestPet($swiperPetId, $actingUserId));
    $pets->shouldReceive('findById')->with(Mockery::on(fn ($id) => $id->equals($targetPetId)))
        ->andReturn(makeTestPet($targetPetId, Id::generate()));

    $swipes = Mockery::mock(SwipeRepositoryInterface::class);
    $swipes->shouldReceive('hasSwiped')->once()->andReturn(false);
    $swipes->shouldReceive('record')->once();
    $swipes->shouldReceive('hasLiked')->once()->andReturn(true);

    $matches = Mockery::mock(PetMatchRepositoryInterface::class);
    $matches->shouldReceive('existsBetween')->once()->andReturn(false);
    $matches->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $matches->shouldReceive('save')->once();

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once();

    $handler = new RecordSwipeHandler($pets, $swipes, $matches, $events);
    $result = $handler->handle(new RecordSwipeCommand($actingUserId->toString(), $swiperPetId->toString(), $targetPetId->toString(), 'like'));

    expect($result->isMatch())->toBeTrue();
});

it('rejects swiping with a pet the actor does not own', function (): void {
    $swiperPetId = Id::generate();
    $targetPetId = Id::generate();

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->with(Mockery::on(fn ($id) => $id->equals($swiperPetId)))
        ->andReturn(makeTestPet($swiperPetId, Id::generate()));

    $swipes = Mockery::mock(SwipeRepositoryInterface::class);
    $matches = Mockery::mock(PetMatchRepositoryInterface::class);
    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new RecordSwipeHandler($pets, $swipes, $matches, $events);
    $handler->handle(new RecordSwipeCommand(Id::generate()->toString(), $swiperPetId->toString(), $targetPetId->toString(), 'like'));
})->throws(PetNotOwnedByActorException::class);

it('rejects swiping own pet', function (): void {
    $actingUserId = Id::generate();
    $swiperPetId = Id::generate();
    $targetPetId = Id::generate();

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->with(Mockery::on(fn ($id) => $id->equals($swiperPetId)))
        ->andReturn(makeTestPet($swiperPetId, $actingUserId));
    $pets->shouldReceive('findById')->with(Mockery::on(fn ($id) => $id->equals($targetPetId)))
        ->andReturn(makeTestPet($targetPetId, $actingUserId));

    $swipes = Mockery::mock(SwipeRepositoryInterface::class);
    $matches = Mockery::mock(PetMatchRepositoryInterface::class);
    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new RecordSwipeHandler($pets, $swipes, $matches, $events);
    $handler->handle(new RecordSwipeCommand($actingUserId->toString(), $swiperPetId->toString(), $targetPetId->toString(), 'like'));
})->throws(CannotSwipeOwnPetException::class);

it('rejects swiping the same target twice', function (): void {
    $actingUserId = Id::generate();
    $swiperPetId = Id::generate();
    $targetPetId = Id::generate();

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->with(Mockery::on(fn ($id) => $id->equals($swiperPetId)))
        ->andReturn(makeTestPet($swiperPetId, $actingUserId));
    $pets->shouldReceive('findById')->with(Mockery::on(fn ($id) => $id->equals($targetPetId)))
        ->andReturn(makeTestPet($targetPetId, Id::generate()));

    $swipes = Mockery::mock(SwipeRepositoryInterface::class);
    $swipes->shouldReceive('hasSwiped')->once()->andReturn(true);
    $swipes->shouldNotReceive('record');

    $matches = Mockery::mock(PetMatchRepositoryInterface::class);
    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new RecordSwipeHandler($pets, $swipes, $matches, $events);
    $handler->handle(new RecordSwipeCommand($actingUserId->toString(), $swiperPetId->toString(), $targetPetId->toString(), 'like'));
})->throws(PetAlreadySwipedException::class);
