<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Repositories;

use App\Modules\Marketplace\Domain\Entities\Dispute;
use App\Shared\Domain\ValueObjects\Id;

interface DisputeRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Dispute $dispute): void;

    public function findById(Id $id): ?Dispute;

    public function findByOrderId(Id $orderId): ?Dispute;

    public function countOpen(): int;
}
