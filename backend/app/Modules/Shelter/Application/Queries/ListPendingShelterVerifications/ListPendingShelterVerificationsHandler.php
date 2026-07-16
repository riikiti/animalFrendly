<?php

declare(strict_types=1);

namespace App\Modules\Shelter\Application\Queries\ListPendingShelterVerifications;

use App\Modules\Shelter\Domain\Entities\Shelter;
use App\Modules\Shelter\Domain\Repositories\ShelterRepositoryInterface;

final class ListPendingShelterVerificationsHandler
{
    public function __construct(private readonly ShelterRepositoryInterface $shelters) {}

    /**
     * @return list<Shelter>
     */
    public function handle(): array
    {
        return $this->shelters->findPendingVerification();
    }
}
