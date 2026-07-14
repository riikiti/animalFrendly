<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaidEscrow = 'paid_escrow';
    case Completed = 'completed';
    case Disputed = 'disputed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
}
