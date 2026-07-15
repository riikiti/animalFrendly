<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class BoostQuotaExceededException extends DomainException
{
    public static function create(): self
    {
        return new self('Лимит бустов по тарифу исчерпан в этом месяце.');
    }
}
