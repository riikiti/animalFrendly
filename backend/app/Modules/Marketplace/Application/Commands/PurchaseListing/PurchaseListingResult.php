<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Commands\PurchaseListing;

use App\Modules\Marketplace\Domain\Entities\Order;

final class PurchaseListingResult
{
    public function __construct(
        public readonly Order $order,
        public readonly string $confirmationUrl,
    ) {}
}
