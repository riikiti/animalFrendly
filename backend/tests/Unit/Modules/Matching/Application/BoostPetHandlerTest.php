<?php

declare(strict_types=1);

use App\Modules\Matching\Application\Commands\BoostPet\BoostPetCommand;
use App\Modules\Matching\Application\Commands\BoostPet\BoostPetHandler;
use App\Modules\Matching\Application\Contracts\SubscriptionFeatureGateInterface;
use App\Modules\Matching\Domain\Exceptions\BoostQuotaExceededException;
use App\Modules\Matching\Domain\Exceptions\PetNotOwnedByActorException;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;

function makeBoostTestPet(Id $id, Id $ownerId): Pet
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

it('boosts the pet when the quota allows it', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $pet = makeBoostTestPet($petId, $ownerId);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldReceive('save')->once()->with(Mockery::on(fn (Pet $p) => $p->isBoosted()));

    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);
    $featureGate->shouldReceive('consume')->once()
        ->with(Mockery::on(fn ($id) => $id->equals($ownerId)), 'boost')->andReturn(true);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once();

    $handler = new BoostPetHandler($pets, $featureGate, $events);
    $result = $handler->handle(new BoostPetCommand($petId->toString(), $ownerId->toString()));

    expect($result->isBoosted())->toBeTrue();
});

it('rejects boosting once the monthly quota is exhausted', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $pet = makeBoostTestPet($petId, $ownerId);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);
    $pets->shouldNotReceive('save');

    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);
    $featureGate->shouldReceive('consume')->once()->andReturn(false);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new BoostPetHandler($pets, $featureGate, $events);
    $handler->handle(new BoostPetCommand($petId->toString(), $ownerId->toString()));
})->throws(BoostQuotaExceededException::class);

it('rejects boosting a pet the actor does not own', function (): void {
    $petId = Id::generate();
    $pet = makeBoostTestPet($petId, Id::generate());

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);

    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);
    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new BoostPetHandler($pets, $featureGate, $events);
    $handler->handle(new BoostPetCommand($petId->toString(), Id::generate()->toString()));
})->throws(PetNotOwnedByActorException::class);
