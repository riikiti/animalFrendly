<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Queries\SearchListings;

use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Search\Application\Contracts\ListingSearchIndexInterface;
use App\Modules\Search\Application\DTO\SearchResultPage;
use App\Shared\Domain\ValueObjects\Id;

final class SearchListingsHandler
{
    public function __construct(
        private readonly ListingSearchIndexInterface $index,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(SearchListingsQuery $query): SearchResultPage
    {
        $actingUser = $this->users->findById(Id::fromString($query->actingUserId));

        return $this->index->search($query, $actingUser?->latitude(), $actingUser?->longitude());
    }
}
