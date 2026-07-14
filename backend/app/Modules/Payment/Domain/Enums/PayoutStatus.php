<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Enums;

enum PayoutStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
}
