<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Breed;
use App\Modules\Catalog\Domain\Entities\Species;
use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetCommand;
use App\Modules\Profile\Application\Commands\CreatePet\CreatePetHandler;
use App\Modules\Profile\Domain\Exceptions\BreedDoesNotBelongToSpeciesException;
use App\Modules\Profile\Domain\Exceptions\SpeciesNotFoundException;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
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
    );
}

it('creates a pet for a valid species', function (): void {
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $pets->shouldReceive('save')->once();

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->with(1)->andReturn(
        new Species(id: 1, slug: 'dog', nameRu: 'Собака', isActive: true),
    );

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $breeds->shouldNotReceive('findById');

    $handler = new CreatePetHandler($pets, $species, $breeds);
    $pet = $handler->handle(makeCreatePetCommand());

    expect($pet->name())->toBe('Рекс');
});

it('rejects an unknown species', function (): void {
    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldNotReceive('save');

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->andReturn(null);

    $breeds = Mockery::mock(BreedRepositoryInterface::class);

    $handler = new CreatePetHandler($pets, $species, $breeds);
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

    $handler = new CreatePetHandler($pets, $species, $breeds);
    $handler->handle(makeCreatePetCommand(['speciesId' => 1, 'breedId' => 10]));
})->throws(BreedDoesNotBelongToSpeciesException::class);
