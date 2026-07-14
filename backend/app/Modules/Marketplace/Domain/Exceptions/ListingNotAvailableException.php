<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class ListingNotAvailableException extends DomainException
{
    public static function create(): self
    {
        return new self('Этот листинг сейчас недоступен для покупки.');
    }
}
