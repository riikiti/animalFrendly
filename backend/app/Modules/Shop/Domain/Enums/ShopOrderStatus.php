<?php

declare(strict_types=1);

namespace App\Modules\Shop\Domain\Enums;

/**
 * Те же состояния, что у сделки маркетплейса: деньги лежат на эскроу, пока обе стороны
 * не подтвердят, см. docs/rules/04-payments-escrow.md.
 */
enum ShopOrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaidEscrow = 'paid_escrow';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Disputed = 'disputed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
}
