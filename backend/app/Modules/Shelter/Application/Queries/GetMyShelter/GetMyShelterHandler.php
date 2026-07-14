<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Queries\GetMyShelter;

use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class GetMyShelterHandler
{
    public function __construct(private readonly ShelterRepositoryInterface $shelters) {}

    public function handle(string $ownerUserId): ?Shelter
    {
        $shelters = $this->shelters->findByOwnerUserId(Id::fromString($ownerUserId));

        return $shelters[0] ?? null;
    }
}
