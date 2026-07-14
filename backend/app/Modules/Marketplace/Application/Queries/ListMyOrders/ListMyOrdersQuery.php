<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Application\Queries\ListMyOrders;

final class ListMyOrdersQuery
{
    /**
     * @param  'buyer'|'seller'  $role
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $role,
    ) {}
}
