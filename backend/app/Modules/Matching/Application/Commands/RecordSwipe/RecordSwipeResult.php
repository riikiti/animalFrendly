<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Commands\RecordSwipe;

use App\Modules\Matching\Domain\Entities\PetMatch;

final class RecordSwipeResult
{
    public function __construct(public readonly ?PetMatch $match) {}

    public function isMatch(): bool
    {
        return $this->match !== null;
    }
}
