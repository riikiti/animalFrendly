<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\PublishShelterAnimal;

final class PublishShelterAnimalCommand
{
    public function __construct(
        public readonly string $shelterId,
        public readonly string $actingUserId,
        public readonly int $speciesId,
        public readonly ?int $breedId,
        public readonly string $name,
        public readonly string $sex,
        public readonly ?string $birthdate,
        public readonly ?string $description,
        public readonly bool $isVaccinated,
    ) {}
}
