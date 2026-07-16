<?php

declare(strict_types=1);

namespace App\Modules\Profile\Application\Commands\CreatePet;

final class CreatePetCommand
{
    /**
     * @param  list<string>  $socialTags
     */
    public function __construct(
        public readonly string $ownerId,
        public readonly int $speciesId,
        public readonly ?int $breedId,
        public readonly string $name,
        public readonly string $sex,
        public readonly ?string $birthdate,
        public readonly string $purpose,
        public readonly ?string $description,
        public readonly bool $isVaccinated,
        public readonly array $socialTags = [],
    ) {}
}
