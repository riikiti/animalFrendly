<?php

declare(strict_types=1);

namespace App\Modules\Matching\Domain\Repositories;

use App\Modules\Matching\Domain\Entities\Swipe;
use App\Shared\Domain\ValueObjects\Id;

interface SwipeRepositoryInterface
{
    public function record(Swipe $swipe): void;

    public function hasSwiped(Id $swiperPetId, Id $targetPetId): bool;

    /**
     * Была ли позитивная (like/super_like) заявка от $fromPetId к $toPetId —
     * используется для определения взаимного мэтча.
     */
    public function hasLiked(Id $fromPetId, Id $toPetId): bool;

    /**
     * @return list<Id>
     */
    public function swipedTargetIds(Id $swiperPetId): array;
}
