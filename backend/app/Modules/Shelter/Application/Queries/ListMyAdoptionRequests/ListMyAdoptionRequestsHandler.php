<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Queries\ListMyAdoptionRequests;

use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class ListMyAdoptionRequestsHandler
{
    public function __construct(private readonly AdoptionRequestRepositoryInterface $requests) {}

    /**
     * @return list<AdoptionRequest>
     */
    public function handle(string $requesterUserId): array
    {
        return $this->requests->findByRequester(Id::fromString($requesterUserId));
    }
}
