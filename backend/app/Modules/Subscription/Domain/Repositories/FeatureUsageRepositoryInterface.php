<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Domain\Repositories;

use App\Modules\Subscription\Domain\Enums\FeatureKey;
use App\Shared\Domain\ValueObjects\Id;

interface FeatureUsageRepositoryInterface
{
    /**
     * Атомарно инкрементирует счётчик расхода фичи за период и возвращает, был ли расход
     * разрешён. При $limit === null лимита нет — расход учитывается, но всегда разрешён.
     */
    public function tryConsume(Id $userId, FeatureKey $featureKey, string $periodStart, ?int $limit): bool;
}
