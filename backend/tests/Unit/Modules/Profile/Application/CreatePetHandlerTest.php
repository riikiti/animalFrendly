<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Breed;
use App\Modules\Catalog\Domain\Entities\Species;
use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetCommand;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetHandler;
use App\Modules\Profile\Application\Contracts\SubscriptionFeatureGateInterface;
use App\Modules\Profile\Domain\Exceptions\BreedDoesNotBelongToSpeciesException;
use App\Modules\Profile\Domain\Exceptions\PetLimitExceededException;
use App\Modules\Profile\Domain\Exceptions\SpeciesNotFoundException;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Shared\Application\DomainEventDispatcherInterface;
use App\Shared\Domain\ValueObjects\Id;

function makeCreatePetCommand(array $overrides = []): CreatePetCommand
{
    return new CreatePetCommand(
        ownerId: $overrides['ownerId'] ?? Id::generate()->toString(),
        speciesId: $overrides['speciesId'] ?? 1,
        breedId: $overrides['breedId'] ?? null,
        name: $overrides['name'] ?? 'Рекс',
        sex: $overrides['sex'] ?? 'male',
        birthdate: $overrides['birthdate'] ?? null,
        purpose: $overrides['purpose'] ?? 'social',
        description: $overrides['description'] ?? null,
        isVaccinated: $overrides['isVaccinated'] ?? false,
        socialTags: $overrides['socialTags'] ?? [],
    );
}

it('creates a pet for a valid species', function (): void {
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $pets->shouldReceive('countByOwnerForMatching')->once()->andReturn(0);
    $pets->shouldReceive('save')->once();

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->with(1)->andReturn(
        new Species(id: 1, slug: 'dog', nameRu: 'Собака', isActive: true),
    );

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $breeds->shouldNotReceive('findById');

    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once();

    $handler = new CreatePetHandler($pets, $species, $breeds, $featureGate, $events);
    $pet = $handler->handle(makeCreatePetCommand());

    expect($pet->name())->toBe('Рекс');
});

it('maps social tags into the created pet', function (): void {
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $pets->shouldReceive('countByOwnerForMatching')->once()->andReturn(0);
    $pets->shouldReceive('save')->once();

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->with(1)->andReturn(
        new Species(id: 1, slug: 'dog', nameRu: 'Собака', isActive: true),
    );

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once();

    $handler = new CreatePetHandler($pets, $species, $breeds, $featureGate, $events);
    $pet = $handler->handle(makeCreatePetCommand(['socialTags' => ['walks', 'friendship']]));

    expect(array_map(fn ($tag) => $tag->value, $pet->socialTags()))->toBe(['walks', 'friendship']);
});

it('rejects an unknown species', function (): void {
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldNotReceive('save');

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->andReturn(null);

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new CreatePetHandler($pets, $species, $breeds, $featureGate, $events);
    $handler->handle(makeCreatePetCommand(['speciesId' => 999]));
})->throws(SpeciesNotFoundException::class);

it('rejects a breed that does not belong to the given species', function (): void {
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldNotReceive('save');

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->with(1)->andReturn(
        new Species(id: 1, slug: 'dog', nameRu: 'Собака', isActive: true),
    );

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $breeds->shouldReceive('findById')->once()->with(10)->andReturn(
        new Breed(id: 10, speciesId: 2, slug: 'siamese', nameRu: 'Сиамская', attributes: [], isActive: true),
    );

    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);
    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new CreatePetHandler($pets, $species, $breeds, $featureGate, $events);
    $handler->handle(makeCreatePetCommand(['speciesId' => 1, 'breedId' => 10]));
})->throws(BreedDoesNotBelongToSpeciesException::class);

it('rejects a second pet without an active subscription', function (): void {
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('countByOwnerForMatching')->once()->andReturn(1);
    $pets->shouldNotReceive('save');

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->with(1)->andReturn(
        new Species(id: 1, slug: 'dog', nameRu: 'Собака', isActive: true),
    );

    $breeds = Mockery::mock(BreedRepositoryInterface::class);

    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);
    $featureGate->shouldReceive('hasUnlimitedPets')->once()->andReturn(false);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);

    $handler = new CreatePetHandler($pets, $species, $breeds, $featureGate, $events);
    $handler->handle(makeCreatePetCommand());
})->throws(PetLimitExceededException::class);

it('does not gate for_sale pets even when the owner already has a matching pet', function (): void {
    // CreateListingHandler (Marketplace) создаёт for_sale-анкеты этим же хендлером — лимит
    // анкет для мэтчинга не должен мешать продавцу выставлять сколько угодно объявлений.
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $pets->shouldNotReceive('countByOwnerForMatching');
    $pets->shouldReceive('save')->once();

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->with(1)->andReturn(
        new Species(id: 1, slug: 'dog', nameRu: 'Собака', isActive: true),
    );

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);
    $featureGate->shouldNotReceive('hasUnlimitedPets');

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once();

    $handler = new CreatePetHandler($pets, $species, $breeds, $featureGate, $events);
    $pet = $handler->handle(makeCreatePetCommand(['purpose' => 'for_sale']));

    expect($pet->purpose()->value)->toBe('for_sale');
});

it('does not gate shelter pets even when the owner already has a matching pet', function (): void {
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $pets->shouldNotReceive('countByOwnerForMatching');
    $pets->shouldReceive('save')->once();

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->with(1)->andReturn(
        new Species(id: 1, slug: 'dog', nameRu: 'Собака', isActive: true),
    );

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);
    $featureGate->shouldNotReceive('hasUnlimitedPets');

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once();

    $handler = new CreatePetHandler($pets, $species, $breeds, $featureGate, $events);
    $pet = $handler->handle(makeCreatePetCommand(['purpose' => 'shelter']));

    expect($pet->purpose()->value)->toBe('shelter');
});

it('allows a second pet with an active subscription', function (): void {
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $pets->shouldReceive('countByOwnerForMatching')->once()->andReturn(1);
    $pets->shouldReceive('save')->once();

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->with(1)->andReturn(
        new Species(id: 1, slug: 'dog', nameRu: 'Собака', isActive: true),
    );

    $breeds = Mockery::mock(BreedRepositoryInterface::class);

    $featureGate = Mockery::mock(SubscriptionFeatureGateInterface::class);
    $featureGate->shouldReceive('hasUnlimitedPets')->once()->andReturn(true);

    $events = Mockery::mock(DomainEventDispatcherInterface::class);
    $events->shouldReceive('dispatch')->once();

    $handler = new CreatePetHandler($pets, $species, $breeds, $featureGate, $events);
    $pet = $handler->handle(makeCreatePetCommand());

    expect($pet->name())->toBe('Рекс');
});
