<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case WaitingForCapture = 'waiting_for_capture';
    case Succeeded = 'succeeded';
    case Canceled = 'canceled';
    case Refunded = 'refunded';
}
