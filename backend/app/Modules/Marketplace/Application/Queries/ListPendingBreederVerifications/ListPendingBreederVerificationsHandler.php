<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Queries\ListPendingBreederVerifications;

use App\Modules\Marketplace\Domain\Entities\Breeder;
use App\Modules\Marketplace\Domain\Repositories\BreederRepositoryInterface;

final class ListPendingBreederVerificationsHandler
{
    public function __construct(private readonly BreederRepositoryInterface $breeders) {}

    /**
     * @return list<Breeder>
     */
    public function handle(): array
    {
        return $this->breeders->findPendingVerification();
    }
}
