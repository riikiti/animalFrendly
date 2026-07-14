<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Commands\RegisterShelter;

use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class RegisterShelterHandler
{
    public function __construct(private readonly ShelterRepositoryInterface $shelters) {}

    public function handle(RegisterShelterCommand $command): Shelter
    {
        $shelter = Shelter::register(
            id: $this->shelters->nextIdentity(),
            ownerUserId: Id::fromString($command->ownerUserId),
            legalName: $command->legalName,
            inn: $command->inn,
            description: $command->description,
        );

        $this->shelters->save($shelter);

        return $shelter;
    }
}
