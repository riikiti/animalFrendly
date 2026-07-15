<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Entities\Breed;
use App\Modules\Catalog\Domain\Entities\Species;
use App\Modules\Catalog\Domain\Repositories\BreedRepositoryInterface;
use App\Modules\Catalog\Domain\Repositories\SpeciesRepositoryInterface;
use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Modules\Profile\Domain\Entities\Pet;
use App\Modules\Profile\Domain\Enums\PetPurpose;
use App\Modules\Profile\Domain\Enums\PetSex;
use App\Modules\Profile\Domain\Enums\PetStatus;
use App\Modules\Search\Application\Indexing\BuildPetDocument;
use App\Shared\Domain\ValueObjects\Id;

function makeSearchTestOwner(Id $id, ?string $city = null, ?float $lat = null, ?float $lng = null): User
{
    $user = User::register(
        id: $id,
        phone: PhoneNumber::fromString('+79261234567'),
        passwordHash: 'hashed-password',
        accountType: AccountType::Owner,
        personalDataConsentGiven: true,
    );

    if ($city !== null || $lat !== null) {
        $user->setLocation('Тестовый адрес', $city, $lat, $lng);
    }

    return $user;
}

function makeSearchTestPet(Id $id, Id $ownerId, PetStatus $status = PetStatus::Active, PetPurpose $purpose = PetPurpose::Social): Pet
{
    return Pet::reconstitute(
        id: $id,
        ownerId: $ownerId,
        speciesId: 1,
        breedId: 10,
        name: 'Рекс',
        sex: PetSex::Male,
        birthdate: null,
        purpose: $purpose,
        description: null,
        isVaccinated: true,
        status: $status,
    );
}

it('builds a document with owner city/coordinates and catalog names', function (): void {
    $ownerId = Id::generate();
    $petId = Id::generate();
    $pet = makeSearchTestPet($petId, $ownerId);
    $owner = makeSearchTestOwner($ownerId, 'Москва', 55.755, 37.617);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($owner);

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->with(1)->andReturn(new Species(1, 'dog', 'Собака', true));

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $breeds->shouldReceive('findById')->once()->with(10)->andReturn(new Breed(10, 1, 'labrador', 'Лабрадор', [], true));

    $builder = new BuildPetDocument($users, $species, $breeds);
    $document = $builder->build($pet);

    expect($document)->not->toBeNull()
        ->and($document['id'])->toBe($petId->toString())
        ->and($document['species_name'])->toBe('Собака')
        ->and($document['breed_name'])->toBe('Лабрадор')
        ->and($document['city'])->toBe('Москва')
        ->and($document['_geo'])->toBe(['lat' => 55.755, 'lng' => 37.617]);
});

it('omits _geo when the owner has no coordinates', function (): void {
    $ownerId = Id::generate();
    $pet = makeSearchTestPet(Id::generate(), $ownerId);
    $owner = makeSearchTestOwner($ownerId);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($owner);

    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $species->shouldReceive('findById')->once()->andReturn(new Species(1, 'dog', 'Собака', true));

    $breeds = Mockery::mock(BreedRepositoryInterface::class);
    $breeds->shouldReceive('findById')->once()->andReturn(new Breed(10, 1, 'labrador', 'Лабрадор', [], true));

    $builder = new BuildPetDocument($users, $species, $breeds);
    $document = $builder->build($pet);

    expect($document)->not->toBeNull()
        ->and(array_key_exists('_geo', $document))->toBeFalse();
});

it('returns null for a hidden pet', function (): void {
    $ownerId = Id::generate();
    $pet = makeSearchTestPet(Id::generate(), $ownerId, PetStatus::Hidden);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $breeds = Mockery::mock(BreedRepositoryInterface::class);

    $builder = new BuildPetDocument($users, $species, $breeds);

    expect($builder->build($pet))->toBeNull();
});

it('returns null for a pet listed for sale (represented via the listings index instead)', function (): void {
    $ownerId = Id::generate();
    $pet = makeSearchTestPet(Id::generate(), $ownerId, PetStatus::Active, PetPurpose::ForSale);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $species = Mockery::mock(SpeciesRepositoryInterface::class);
    $breeds = Mockery::mock(BreedRepositoryInterface::class);

    $builder = new BuildPetDocument($users, $species, $breeds);

    expect($builder->build($pet))->toBeNull();
});
