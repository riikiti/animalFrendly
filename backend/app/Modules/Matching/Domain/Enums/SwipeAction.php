<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Enums;

enum SwipeAction: string
{
    case Like = 'like';
    case Dislike = 'dislike';
    case SuperLike = 'super_like';

    public function isPositive(): bool
    {
        return $this === self::Like || $this === self::SuperLike;
    }
}
