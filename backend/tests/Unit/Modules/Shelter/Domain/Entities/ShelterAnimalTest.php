<?php

declare(strict_types=1);

use App\Modules\Shelter\Domain\Entities\ShelterAnimal;
use App\Modules\Shelter\Domain\Enums\ShelterAnimalStatus;
use App\Modules\Shelter\Domain\Exceptions\ShelterAnimalNotAvailableException;
use App\Shared\Domain\ValueObjects\Id;

it('is published as available', function (): void {
    $animal = ShelterAnimal::publish(Id::generate(), Id::generate(), Id::generate());

    expect($animal->status())->toBe(ShelterAnimalStatus::Available)
        ->and($animal->isAvailable())->toBeTrue();
});

it('becomes reserved and can then be marked adopted', function (): void {
    $animal = ShelterAnimal::publish(Id::generate(), Id::generate(), Id::generate());

    $animal->reserve();
    expect($animal->status())->toBe(ShelterAnimalStatus::Reserved);

    $animal->markAdopted();
    expect($animal->status())->toBe(ShelterAnimalStatus::Adopted);
});

it('cannot be reserved twice', function (): void {
    $animal = ShelterAnimal::publish(Id::generate(), Id::generate(), Id::generate());
    $animal->reserve();

    $animal->reserve();
})->throws(ShelterAnimalNotAvailableException::class);
