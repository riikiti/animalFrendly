<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

enum DisputeResolution: string
{
    case SellerWins = 'seller_wins';
    case BuyerWins = 'buyer_wins';
}
