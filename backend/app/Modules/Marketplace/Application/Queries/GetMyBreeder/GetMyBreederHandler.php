<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Queries\GetMyBreeder;

use App\Modules\Marketplace\Domain\Entities\Breeder;
use App\Modules\Marketplace\Domain\Repositories\BreederRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

final class GetMyBreederHandler
{
    public function __construct(private readonly BreederRepositoryInterface $breeders) {}

    public function handle(string $ownerUserId): ?Breeder
    {
        return $this->breeders->findByOwnerUserId(Id::fromString($ownerUserId));
    }
}
