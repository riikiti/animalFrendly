<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Breed;
use App\Modules\Catalog\Domain\Entities\Species;
use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Marketplace\Domain\Entities\Listing;
use App\Modules\Profile\Domain\Repositories\PetRepositoryInterface;
use App\Modules\Search\Application\Indexing\BuildListingDocument;
use App\Shared\Domain\ValueObjects\Id;
use App\Shared\Domain\ValueObjects\Money;

it('builds a document for a published listing', function (): void {
    $sellerId = Id::generate();
    $petId = Id::generate();
    $listing = Listing::create(Id::generate(), $sellerId, $petId, Money::fromMinorUnits(50_000));
    $listing->publish();

    $pet = makeSearchTestPet($petId, $sellerId);
    $owner = makeSearchTestOwner($sellerId, 'Казань', 55.796, 49.106);

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn($pet);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($owner);

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->andReturn(new Species(1, 'dog', 'Собака', true));

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $breeds->shouldReceive('findById')->once()->andReturn(new Breed(10, 1, 'labrador', 'Лабрадор', [], true));

    $builder = new BuildListingDocument($pets, $users, $species, $breeds);
    $document = $builder->build($listing);

    expect($document)->not->toBeNull()
        ->and($document['id'])->toBe($listing->id()->toString())
        ->and($document['pet_name'])->toBe('Рекс')
        ->and($document['city'])->toBe('Казань')
        ->and($document['price_amount'])->toBe(50_000)
        ->and($document['_geo'])->toBe(['lat' => 55.796, 'lng' => 49.106]);
});

it('returns null for a draft listing', function (): void {
    $listing = Listing::create(Id::generate(), Id::generate(), Id::generate(), Money::fromMinorUnits(50_000));

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $users = Mockery::mock(UserRepositoryInterface::class);
    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $breeds = Mockery::mock(BreedRepositoryInterface::class);

    $builder = new BuildListingDocument($pets, $users, $species, $breeds);

    expect($builder->build($listing))->toBeNull();
});

it('returns null when the referenced pet no longer exists', function (): void {
    $listing = Listing::create(Id::generate(), Id::generate(), Id::generate(), Money::fromMinorUnits(50_000));
    $listing->publish();

    $pets = Mockery::mock(PetRepositoryInterface::class);
    $pets->shouldReceive('findById')->once()->andReturn(null);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $breeds = Mockery::mock(BreedRepositoryInterface::class);

    $builder = new BuildListingDocument($pets, $users, $species, $breeds);

    expect($builder->build($listing))->toBeNull();
});
