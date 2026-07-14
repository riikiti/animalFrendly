<?php

declare(strict_types=1);

namespace App\Modules\Marketplace\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class NotOrderPartyException extends DomainException
{
    public static function create(): self
    {
        return new self('Действие доступно только покупателю или продавцу этой сделки.');
    }
}
