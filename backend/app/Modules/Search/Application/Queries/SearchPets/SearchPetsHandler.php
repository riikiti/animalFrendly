<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Queries\SearchPets;

use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Search\Application\Contracts\PetSearchIndexInterface;
use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Shared\Domain\ValueObjects\Id;

final class SearchPetsHandler
{
    public function __construct(
        private readonly PetSearchIndexInterface $index,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(SearchPetsQuery $query): SearchResultPage
    {
        $actingUser = $this->users->findById(Id::fromString($query->actingUserId));

        return $this->index->search($query, $actingUser?->latitude(), $actingUser?->longitude());
    }
}
