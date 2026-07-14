<?php

declare(strict_types=1);

use App\Modules\Catalog\Application\Queries\ListBreeds\ListBreedsHandler;
use App\Modules\Catalog\Application\Queries\ListBreeds\ListBreedsQuery;
use App\Modules\Catalog\Domain\Entities\Breed;
use App\Modules\Catalog\Domain\Entities\Species;
use App\Modules\Catalog\Domain\Exceptions\SpeciesNotFoundException;
use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;

it('returns breeds for a known species', function (): void {
    $species = new Species(id: 1, slug: 'dog', nameRu: 'Собака', isActive: true);
    $breed = new Breed(id: 10, speciesId: 1, slug: 'labrador', nameRu: 'Лабрадор', attributes: [], isActive: true);

    $speciesRepo = Mockery::mock(SpeciesRepositoryInterface::class);
    $speciesRepo->shouldReceive('findBySlug')->once()->with('dog')->andReturn($species);

    $breedsRepo = Mockery::mock(BreedRepositoryInterface::class);
    $breedsRepo->shouldReceive('activeBySpeciesId')->once()->with(1)->andReturn([$breed]);

    $handler = new ListBreedsHandler($speciesRepo, $breedsRepo);

    expect($handler->handle(new ListBreedsQuery('dog')))->toBe([$breed]);
});

it('rejects an unknown species slug', function (): void {
    $speciesRepo = Mockery::mock(SpeciesRepositoryInterface::class);
    $speciesRepo->shouldReceive('findBySlug')->once()->with('dragon')->andReturn(null);

    $breedsRepo = Mockery::mock(BreedRepositoryInterface::class);
    $breedsRepo->shouldNotReceive('activeBySpeciesId');

    $handler = new ListBreedsHandler($speciesRepo, $breedsRepo);

    $handler->handle(new ListBreedsQuery('dragon'));
})->throws(SpeciesNotFoundException::class);
