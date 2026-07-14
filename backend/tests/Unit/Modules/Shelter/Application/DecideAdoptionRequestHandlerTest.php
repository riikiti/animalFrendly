<?php

declare(strict_types=1);

use App\Modules\Shelter\Application\Commands\DecideAdoptionRequest\DecideAdoptionRequestCommand;
use App\Modules\Shelter\Application\Commands\DecideAdoptionRequest\DecideAdoptionRequestHandler;
use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use App\Modules\Shelter\Domain\Exceptions\NotShelterOwnerException;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterAnimalRepositoryInterface;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;

it('approves a request, reserves the animal, and dispatches AdoptionRequestApproved', function (): void {
    $shelterOwnerId = Id::generate();
    $shelter = Shelter::register(Id::generate(), $shelterOwnerId, 'Добрые лапы', null, null);
    $shelter->verify(Id::generate());

    $shelterAnimal = ShelterAnimal::publish(Id::generate(), $shelter->id(), Id::generate());
    $request = AdoptionRequest::create(Id::generate(), $shelterAnimal->id(), Id::generate(), null);

    $requests = Mockery::mock(AdoptionRequestRepositoryInterface::class);
    $requests->shouldReceive('findById')->once()->andReturn($request);
    $requests->shouldReceive('save')->once();

    $shelterAnimals = Mockery::mock(ShelterAnimalRepositoryInterface::class);
    $shelterAnimals->shouldReceive('findById')->once()->andReturn($shelterAnimal);
    $shelterAnimals->shouldReceive('save')->once();

    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $shelters->shouldReceive('findById')->once()->andReturn($shelter);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once();

    $handler = new DecideAdoptionRequestHandler($requests, $shelterAnimals, $shelters, $events);
    $result = $handler->handle(new DecideAdoptionRequestCommand($request->id()->toString(), $shelterOwnerId->toString(), true));

    expect($result->status()->value)->toBe('approved')
        ->and($shelterAnimal->isAvailable())->toBeFalse();
});

it('rejects a decision from someone who does not own the shelter', function (): void {
    $shelter = Shelter::register(Id::generate(), Id::generate(), 'Добрые лапы', null, null);
    $shelter->verify(Id::generate());

    $shelterAnimal = ShelterAnimal::publish(Id::generate(), $shelter->id(), Id::generate());
    $request = AdoptionRequest::create(Id::generate(), $shelterAnimal->id(), Id::generate(), null);

    $requests = Mockery::mock(AdoptionRequestRepositoryInterface::class);
    $requests->shouldReceive('findById')->once()->andReturn($request);
    $requests->shouldNotReceive('save');

    $shelterAnimals = Mockery::mock(ShelterAnimalRepositoryInterface::class);
    $shelterAnimals->shouldReceive('findById')->once()->andReturn($shelterAnimal);
    $shelterAnimals->shouldNotReceive('save');

    $shelters = Mockery::mock(ShelterRepositoryInterface::class);
    $shelters->shouldReceive('findById')->once()->andReturn($shelter);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldNotReceive('dispatch');

    $handler = new DecideAdoptionRequestHandler($requests, $shelterAnimals, $shelters, $events);
    $handler->handle(new DecideAdoptionRequestCommand($request->id()->toString(), Id::generate()->toString(), true));
})->throws(NotShelterOwnerException::class);
