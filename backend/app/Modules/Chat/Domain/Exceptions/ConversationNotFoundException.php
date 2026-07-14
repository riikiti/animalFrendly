<?php

declare(strict_types=1);

namespace App\Modules\Chat\Domain\Exceptions;

use App\Shared\Domain\Exceptions\DomainException;

final class ConversationNotFoundException extends DomainException
{
    public static function forId(string $id): self
    {
        return new self("Беседа «{$id}» не найдена.");
    }

    public static function forMatchId(string $matchId): self
    {
        return new self("Беседа для мэтча «{$matchId}» не найдена.");
    }
}
