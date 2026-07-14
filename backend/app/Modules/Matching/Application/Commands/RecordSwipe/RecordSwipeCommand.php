<?php

declare(strict_types=1);

namespace App\Modules\Matching\Application\Commands\RecordSwipe;

final class RecordSwipeCommand
{
    public function __construct(
        public readonly string $actingUserId,
        public readonly string $swiperPetId,
        public readonly string $targetPetId,
        public readonly string $action,
    ) {}
}
