<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Queries\GetShelter;

use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class GetShelterHandler
{
    public function __construct(private readonly ShelterRepositoryInterface $shelters) {}

    /**
     * Видимость (публично видны только верифицированные, кроме как самому владельцу) —
     * решает контроллер, не хендлер.
     */
    public function handle(GetShelterQuery $query): ?Shelter
    {
        return $this->shelters->findById(Id::fromString($query->shelterId));
    }
}
