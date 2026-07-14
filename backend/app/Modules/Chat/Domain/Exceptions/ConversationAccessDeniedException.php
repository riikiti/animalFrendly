<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class ConversationAccessDeniedException extends DomainException
{
    public static function create(): self
    {
        return new self('У вас нет доступа к этой беседе.');
    }
}
