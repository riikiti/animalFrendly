<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class InvalidReportStatusTransitionException extends DomainException
{
    public static function create(): self
    {
        return new self('Эта жалоба уже рассмотрена.');
    }
}
