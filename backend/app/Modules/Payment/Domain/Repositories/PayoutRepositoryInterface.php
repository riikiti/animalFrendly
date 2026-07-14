<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Repositories;

use App\Modules\Payment\Domain\Entities\Payout;
use App\Shared\Domain\ValueObjects\Id;

interface PayoutRepositoryInterface
{
    public function nextIdentity(): Id;

    public function save(Payout $payout): void;

    public function findByOrderId(Id $orderId): ?Payout;
}
