<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Exceptions;

use App\Modules\Matching\Domain\Enums\SwipeAction;
use App\Shared\Domain\Exceptions\DomainException;

final class SwipeQuotaExceededException extends DomainException
{
    public static function for(SwipeAction $action): self
    {
        $message = $action === SwipeAction::SuperLike
            ? 'Лимит супер-лайков по тарифу исчерпан на этой неделе.'
            : 'Дневной лимит лайков по тарифу исчерпан.';

        return new self($message);
    }
}
